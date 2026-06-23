# Filtros Multi Dropdown

## Objetivo

O componente Blade `x-filters.multi-dropdown` centraliza o padrão de filtro com múltipla seleção usado em telas Livewire.

Ele renderiza:

- botão dropdown com contador de selecionados;
- lista de checkboxes com `wire:model.defer`;
- contador por opção;
- menu com `data-bs-auto-close="outside"` para permitir marcar várias opções antes de aplicar.

## Arquivo

```text
resources/views/components/filters/multi-dropdown.blade.php
```

## Uso básico

```blade
<x-filters.multi-dropdown
    label="Rubrica"
    model="rubricaFilters"
    :options="$rubricaFilterOptions"
    :selected="$rubricaFilters"
    key-prefix="rubrica-filter" />
```

## Props

| Prop | Tipo | Descrição |
| --- | --- | --- |
| `label` | string | Texto exibido no botão do dropdown. |
| `model` | string | Nome da propriedade Livewire usada no `wire:model.defer`. |
| `options` | array | Lista de opções disponíveis. |
| `selected` | array | Valores atualmente selecionados, usado para exibir o contador no botão. |
| `keyPrefix` | string | Prefixo do `wire:key` de cada checkbox. |
| `emptyText` | string | Texto exibido quando não houver opções. |

Cada item de `options` deve ter este formato:

```php
[
    'value' => 'DESENHO',
    'count' => 12,
]
```

## Exemplo na tela de Desenho

Na tela `resources/views/livewire/services/desenho/main.blade.php`, os filtros da nota usam o componente para:

- `Status da nota`: filtra pelo status atual exibido na coluna `NStatus`.
- `Localização`: filtra por `notes.lexp`.
- `Rubrica`: filtra por `notes.rubrica`.

Os filtros são aplicados no componente Livewire:

```text
app/Http/Livewire/Services/Desenho/Main.php
```

O estado fica nas propriedades:

```php
public array $noteStatusFilters = [];
public array $locationFilters = [];
public array $rubricaFilters = [];
```

As opções são calculadas por computed properties:

```php
getNoteStatusFilterOptionsProperty()
getLocationFilterOptionsProperty()
getRubricaFilterOptionsProperty()
```

## Aplicação dos filtros

O componente Blade não executa a query. Ele apenas vincula os checkboxes ao estado Livewire.

A query deve aplicar os valores selecionados no componente pai. Na tela de Desenho, isso acontece em `baseListQuery()`:

```php
->when(count($this->selectedFilterValues($this->rubricaFilters)), function ($q) {
    return $q->whereIn('notes.rubrica', $this->selectedFilterValues($this->rubricaFilters));
})
```

O botão `Aplicar filtros` chama `applyFilters()`, que reseta a paginação. O botão `Limpar` chama `clearAdvancedFilters()`.

## Boas práticas

- Gere as opções a partir da mesma query base da listagem.
- Ao montar opções de um filtro, ignore apenas o próprio filtro para manter contadores coerentes com os demais filtros ativos.
- Use `wire:model.defer` para evitar recarregar a lista a cada checkbox marcado.
- Mantenha a aplicação final em um botão explícito quando o filtro puder ter muitas opções.
- Use `key-prefix` único por filtro para evitar colisão de `wire:key`.
