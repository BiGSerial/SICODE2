<?php

namespace App\Services\Audit;

/**
 * Lê os snapshots JSON de uma auditoria (`before`/`after`): contexto indexável (nota, atividade, registro) e diferença real entre eles.
 * Usada na gravação (`Audit::creating`), no backfill e na tela, para os três concordarem.
 */
class AuditPayload
{
    /** Campos que mudam a cada gravação e não dizem nada ao auditor. */
    public const IGNORED_FIELDS = ['created_at', 'updated_at', 'deleted_at'];

    public static function decode(array|string|null $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Campos que mudaram, `campo => [antes, depois]` com os valores originais. O banco devolve `5` num lado e `"5"` no outro,
     * e `0`/`false`/`null` aparecem trocados; comparar pelo texto normalizado evita "mudanças" que não mudaram nada.
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function diff(array $before, array $after): array
    {
        $changes = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $field) {
            if (in_array($field, self::IGNORED_FIELDS, true)) {
                continue;
            }

            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if (self::normalize($old) !== self::normalize($new)) {
                $changes[$field] = [$old, $new];
            }
        }

        return $changes;
    }

    /**
     * @return array{note_id: ?int, service_id: ?string, record_id: ?string, changes_count: ?int, changed_fields: ?string}
     */
    public static function context(array|string|null $before, array|string|null $after, ?string $action = null): array
    {
        $before = self::decode($before);
        $after  = self::decode($after);
        $pick   = fn (string $key) => $after[$key] ?? $before[$key] ?? null;

        $noteId    = $pick('note_id');
        $serviceId = $pick('service_id');
        $recordId  = $pick('id');
        $changes   = $action === 'updated' ? array_keys(self::diff($before, $after)) : null;

        return [
            'note_id'        => is_numeric($noteId) ? (int) $noteId : null,
            'service_id'     => is_scalar($serviceId) && $serviceId !== '' ? (string) $serviceId : null,
            'record_id'      => is_scalar($recordId) && $recordId !== '' ? (string) $recordId : null,
            'changes_count'  => $changes === null ? null : count($changes),
            'changed_fields' => $changes ? ',' . implode(',', $changes) . ',' : null,
        ];
    }

    private static function normalize(mixed $value): string
    {
        return match (true) {
            $value === null  => '',
            is_bool($value)  => $value ? '1' : '0',
            is_array($value) => json_encode($value),
            default          => self::localDate(trim((string) $value)) ?? trim((string) $value),
        };
    }

    /**
     * O `before` guarda datas como ISO em UTC (`2026-09-23T17:44:24.000000Z`) e o `after` como `Y-m-d H:i:s` no fuso do app; sem
     * converter, toda data parecia ter mudado. Devolve a data no fuso do app, ou null se o texto não for uma data ISO em UTC.
     */
    public static function localDate(string $value): ?string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/', $value)) {
            return null;
        }

        return \Carbon\Carbon::parse($value)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }
}
