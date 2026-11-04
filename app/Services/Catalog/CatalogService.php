<?php

namespace App\Services\Catalog;

use App\Support\Catalog\{CatalogException, CatalogRegistry};
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\QueryException;
use Illuminate\Support\{Collection, Str};
use Illuminate\Support\Facades\DB;

/**
 * Motor de CRUD dos catálogos descritos em `CatalogRegistry`: lista, cria (uma ou várias de uma vez), edita, liga/desliga,
 * reordena e exclui. Quem chama (a tela) só passa `catálogo`, `nível` e ids; toda regra de nome, ordem e exclusão mora aqui.
 *
 * Regras: nome aparado, sem espaços duplos e único dentro do mesmo pai; ordem sempre 1..n (reordenar reindexa os irmãos);
 * item em uso nunca é excluído (a tela sugere desativar); e a tela nunca escreve em coluna que o nível não declarou.
 */
class CatalogService
{
    public const NAME_MAX = 120;

    /** @return array<string, mixed> */
    public function level(string $catalog, int $level): array
    {
        $definition = CatalogRegistry::find($catalog) ?? throw new CatalogException('Catálogo desconhecido.');

        return $definition['levels'][$level] ?? throw new CatalogException('Nível desconhecido.');
    }

    /** Linhas de um nível (já ordenadas, com a contagem de uso). Sem pai, os níveis acima do primeiro vêm vazios. */
    public function rows(string $catalog, int $level, ?int $parentId): Collection
    {
        $config = $this->level($catalog, $level);

        if ($level > 0 && !$parentId) {
            return collect();
        }

        return $this->query($config, $parentId)
            ->withCount(array_keys($config['usage'] ?? []))
            ->get();
    }

    /** Quantos registros o catálogo tem no 1º nível (para o seletor). */
    public function topCount(string $catalog): int
    {
        return $this->query($this->level($catalog, 0), null)->count();
    }

    /** @param array<string, mixed> $data name, description?, code?, active?, flags e números do nível */
    public function create(string $catalog, int $level, ?int $parentId, array $data): Model
    {
        $config = $this->level($catalog, $level);
        $this->requireParent($config, $level, $parentId);

        $attributes = $this->attributes($config, $data, null) + $this->parentAttributes($config, $level, $parentId);

        $this->guardDuplicate($config, $parentId, $attributes['name'], null);

        if ($order = $config['order'] ?? null) {
            $attributes[$order] = ((int) $this->query($config, $parentId)->max($order)) + 1;
        }

        return $this->persist(fn () => $config['model']::query()->create($attributes));
    }

    /** Uma linha por item; ignora vazias e nomes que já existem. @return array{created: int, skipped: int} */
    public function createMany(string $catalog, int $level, ?int $parentId, string $payload): array
    {
        $config = $this->level($catalog, $level);
        $this->requireParent($config, $level, $parentId);

        $names = collect(preg_split('/\R|;/u', $payload) ?: [])
            ->map(fn ($line) => $this->normalizeName($config, (string) $line))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values();

        if ($names->isEmpty()) {
            throw new CatalogException('Cole ao menos um nome (um por linha).');
        }

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($catalog, $level, $parentId, $names, &$created, &$skipped) {
            foreach ($names as $name) {
                try {
                    $this->create($catalog, $level, $parentId, ['name' => $name]);
                    $created++;
                } catch (CatalogException) {
                    $skipped++;
                }
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** @param array<string, mixed> $data */
    public function update(string $catalog, int $level, int $id, array $data): Model
    {
        $config = $this->level($catalog, $level);
        $model  = $this->find($config, $level, $id);

        $attributes = $this->attributes($config, $data, $model);

        $this->guardDuplicate($config, $this->parentOf($config, $model), $attributes['name'], $model->getKey());

        return $this->persist(function () use ($model, $attributes) {
            $model->update($attributes);

            return $model;
        });
    }

    public function toggleActive(string $catalog, int $level, int $id): Model
    {
        $config = $this->level($catalog, $level);

        if (empty($config['active'])) {
            throw new CatalogException('Este cadastro não tem ativar/desativar.');
        }

        $model = $this->find($config, $level, $id);
        $model->update(['active' => !$model->active]);

        return $model;
    }

    public function toggleFlag(string $catalog, int $level, int $id, string $flag): Model
    {
        $config = $this->level($catalog, $level);

        if (!isset($config['flags'][$flag])) {
            throw new CatalogException('Opção desconhecida.');
        }

        $model = $this->find($config, $level, $id);
        $value = !$model->{$flag};

        if (!$value && $this->wouldLeaveNoFlag($config, $model, $flag)) {
            throw new CatalogException('Deixe ao menos uma opção ligada (' . $this->flagLabels($config) . ').');
        }

        $model->update([$flag => $value]);

        return $model;
    }

    /** Troca de lugar com o vizinho (`up`/`down`), reindexando os irmãos em 1..n para não depender de números repetidos. */
    public function move(string $catalog, int $level, int $id, string $direction): void
    {
        $config = $this->level($catalog, $level);
        $order  = $config['order'] ?? throw new CatalogException('Este cadastro é ordenado por nome.');
        $model  = $this->find($config, $level, $id);

        DB::transaction(function () use ($config, $order, $model, $direction) {
            $siblings = $this->query($config, $this->parentOf($config, $model))->lockForUpdate()->get()->values();
            $index    = $siblings->search(fn ($row) => $row->getKey() === $model->getKey());
            $target   = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || $target < 0 || $target >= $siblings->count()) {
                return;
            }

            $ids                          = $siblings->pluck($siblings->first()->getKeyName())->all();
            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

            foreach ($ids as $position => $key) {
                $config['model']::query()->whereKey($key)->update([$order => $position + 1]);
            }
        });
    }

    /** Em uso nunca exclui. Sem coluna `active` (Retorno interno), a mensagem explica que só dá para excluir o que não tem uso. */
    public function delete(string $catalog, int $level, int $id): void
    {
        $config = $this->level($catalog, $level);
        $model  = $this->find($config, $level, $id);

        if ($reason = $this->usageReason($config, $model)) {
            throw new CatalogException(
                'Não dá para excluir: ' . $reason . '.' . (!empty($config['active']) ? ' Desative para tirar das listas sem perder o histórico.' : '')
            );
        }

        $this->persist(fn () => $model->delete());
    }

    // ------------------------------------------------------------------ internos

    private function query(array $config, ?int $parentId): Builder
    {
        $query = $config['model']::query();
        $key   = (new $config['model']())->getKeyName();
        $this->scope($query, $config, $parentId);

        if ($order = $config['order'] ?? null) {
            $query->orderBy($order);
        } else {
            $query->orderBy('name');
        }

        return $query->orderBy($key);
    }

    /** Escopo do pai: coluna FK, ou `parent_id IS NULL` no 1º nível de uma tabela auto-relacionada. */
    private function scope(Builder $query, array $config, ?int $parentId): void
    {
        $parent = $config['parent'] ?? null;

        if ($parent === 'self') {
            $query->whereNull('parent_id');
        } elseif ($parent) {
            $query->where($parent, $parentId);
        }
    }

    private function find(array $config, int $level, int $id): Model
    {
        // Tabela auto-relacionada (Qualidade): o id de uma subcategoria não pode ser editado como se fosse motivo, nem o contrário.
        $query = $config['model']::query();

        match ($config['parent'] ?? null) {
            'self'      => $query->whereNull('parent_id'),
            'parent_id' => $query->whereNotNull('parent_id'),
            default     => null,
        };

        return $query->find($id) ?? throw new CatalogException('Registro não encontrado: atualize a página.');
    }

    private function requireParent(array $config, int $level, ?int $parentId): void
    {
        if (($config['parent'] ?? null) && $config['parent'] !== 'self' && !$parentId) {
            throw new CatalogException('Escolha primeiro o item de cima.');
        }
    }

    /** @return array<string, int|null> */
    private function parentAttributes(array $config, int $level, ?int $parentId): array
    {
        $parent = $config['parent'] ?? null;

        return $parent && $parent !== 'self' ? [$parent => $parentId] : [];
    }

    private function parentOf(array $config, Model $model): ?int
    {
        $parent = $config['parent'] ?? null;

        return $parent && $parent !== 'self' ? (int) $model->{$parent} : null;
    }

    private function normalizeName(array $config, string $name): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));

        return !empty($config['uppercase']) ? mb_strtoupper($name) : $name;
    }

    /** Só colunas que o nível declarou; o que não veio mantém o valor atual (ou o padrão, na criação). */
    private function attributes(array $config, array $data, ?Model $current): array
    {
        $name = $this->normalizeName($config, (string) ($data['name'] ?? $current?->name ?? ''));

        if ($name === '') {
            throw new CatalogException('Informe o nome.');
        }

        if (mb_strlen($name) > self::NAME_MAX) {
            throw new CatalogException('O nome pode ter até ' . self::NAME_MAX . ' caracteres.');
        }

        $attributes = ['name' => $name];

        if (!empty($config['description']) && array_key_exists('description', $data)) {
            $attributes['description'] = ($text = trim((string) $data['description'])) === '' ? null : mb_substr($text, 0, 1000);
        }

        if (!empty($config['code']) && array_key_exists('code', $data)) {
            $attributes['code'] = ($code = trim((string) $data['code'])) === '' ? null : mb_substr($code, 0, 60);
        }

        if (!empty($config['active'])) {
            $attributes['active'] = array_key_exists('active', $data) ? (bool) $data['active'] : ($current?->active ?? true);
        }

        foreach ($config['flags'] ?? [] as $flag => $meta) {
            $attributes[$flag] = array_key_exists($flag, $data) ? (bool) $data[$flag] : ($current?->{$flag} ?? $meta['default']);
        }

        foreach ($config['numbers'] ?? [] as $field => $meta) {
            $value              = array_key_exists($field, $data) ? (int) $data[$field] : ($current?->{$field} ?? $meta['default']);
            $attributes[$field] = max($meta['min'], $value);
        }

        if (!empty($config['atLeastOne']) && !collect($config['atLeastOne'])->contains(fn ($flag) => $attributes[$flag] ?? false)) {
            throw new CatalogException('Deixe ao menos uma opção ligada (' . $this->flagLabels($config) . ').');
        }

        if (!empty($config['code']) && !empty($attributes['code'])) {
            $taken = $config['model']::query()->where('code', $attributes['code'])
                ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))->exists();

            if ($taken) {
                throw new CatalogException('O código "' . $attributes['code'] . '" já está em uso.');
            }
        }

        return $attributes;
    }

    private function guardDuplicate(array $config, ?int $parentId, string $name, int|string|null $ignoreId): void
    {
        $query = $config['model']::query()->where('name', $name);
        $this->scope($query, $config, $parentId);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw new CatalogException('Já existe "' . $name . '" aqui.');
        }

        // Cadastros com identificador (slug) imutável: nomes que viram o mesmo slug colidiriam no banco.
        if (!empty($config['slug'])) {
            $slug = Str::slug($name);

            if ($slug === '') {
                throw new CatalogException('Use ao menos uma letra ou número no nome.');
            }

            $clash = $config['model']::query()->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId));
            $this->scope($clash, $config, $parentId);

            if ($ignoreId === null && $clash->exists()) {
                throw new CatalogException('Já existe um item com nome equivalente ("' . $slug . '").');
            }
        }
    }

    private function wouldLeaveNoFlag(array $config, Model $model, string $flag): bool
    {
        $group = $config['atLeastOne'] ?? [];

        return in_array($flag, $group, true)
            && !collect($group)->reject(fn ($other) => $other === $flag)->contains(fn ($other) => (bool) $model->{$other});
    }

    private function flagLabels(array $config): string
    {
        return collect($config['atLeastOne'] ?? [])->map(fn ($flag) => $config['flags'][$flag]['label'])->implode(' ou ');
    }

    /** Texto do que impede a exclusão ("2 subcategorias e 5 pedidos"), ou null se está livre. */
    private function usageReason(array $config, Model $model): ?string
    {
        $parts = [];

        foreach ($config['usage'] ?? [] as $relation => $label) {
            if (($count = $model->{$relation}()->count()) > 0) {
                $parts[] = $count . ' ' . $label;
            }
        }

        return $parts ? 'tem ' . implode(' e ', $parts) : null;
    }

    private function persist(\Closure $operation): mixed
    {
        try {
            return $operation();
        } catch (QueryException $exception) {
            // Corrida entre dois cliques, ou FK que o motor não conhecia: nunca vaza SQL para a tela.
            report($exception);

            throw new CatalogException('Não foi possível salvar: o nome já existe ou o item está em uso.');
        }
    }
}
