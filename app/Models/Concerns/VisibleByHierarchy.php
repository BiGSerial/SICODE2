<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

// TODO: montar o restante do scopo de hierarquia de usuários (visibilidade por delegações, etc.)
trait VisibleByHierarchy
{
    /**
     * Coluna que guarda o usuário responsável (UUID char(36)).
     * Overridar na Model quando não for 'user_id'.
     *
     * @var string
     */
    protected string $assigneeColumn = 'user_id';

    /**
     * Retorna o nome da coluna do responsável respeitando override na Model.
     */
    protected function getAssigneeColumn(): string
    {
        // permite sobrescrever com propriedade pública/privada na Model
        return property_exists($this, 'assigneeColumn') ? $this->assigneeColumn : 'user_id';
    }

    /**
     * Escopo: restringe os registros à visão do $viewerId (árvore nativa + delegações vigentes).
     * Exige a VIEW 'user_visibility_current' criada nas migrations.
     *
     * @param  Builder  $q
     * @param  string   $viewerId  UUID do usuário visualizador
     */
    public function scopeVisibleTo(Builder $q, string $viewerId): Builder
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = $q->getModel();
        $tbl   = $model->getTable();
        $col   = $this->getAssigneeColumn();

        // Usamos joinSub para evitar conflito de aliases e facilitar EXPLAIN.
        $sub = DB::table('user_visibility_current')
            ->selectRaw('viewer_id, descendant_id');

        return $q->joinSub($sub, 'uvis', function ($join) use ($tbl, $col) {
            $join->on('uvis.descendant_id', '=', "{$tbl}.{$col}");
        })
                ->where('uvis.viewer_id', $viewerId)
                ->select("{$tbl}.*")
                ->distinct();
    }

    /**
     * Escopo: recorta por uma subárvore específica (nó da hierarquia).
     * Útil para o filtro "Minha hierarquia" (Programador X, Engenheiro Y, etc.).
     *
     * @param  Builder  $q
     * @param  string   $nodeId  UUID do nó (usuário) cujo "abaixo" queremos
     */
    public function scopeWithinNode(Builder $q, string $nodeId): Builder
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = $q->getModel();
        $tbl   = $model->getTable();
        $col   = $this->getAssigneeColumn();

        return $q->whereIn("{$tbl}.{$col}", function ($sub) use ($nodeId) {
            $sub->from('user_closure')
                ->select('descendant_id')
                ->where('ancestor_id', $nodeId);
        });
    }
}
