<?php

namespace App\Services;

use App\Models\{Note, Operation, Order};
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ManualRecordImportService
{
    private const NOTE_FIELDS = [
        'note', 'created_by', 'dt_created', 'dt_status', 'user', 'numPedido',
        'pze', 'num_material', 'material', 'nexp', 'lexp', 'nstats', 'status',
        'rubrica', 'centerjob', 'type_note', 'mesalization', 'txpriority',
    ];

    private const ORDER_FIELDS = [
        'ordem', 'descricao', 'locInstalacao', 'cenPlan', 'prioridade',
        'statusSist', 'statusUser', 'cenTrab', 'gpm', 'custPlanejado',
        'custRealizado', 'modifPor', 'pep', 'conjunto', 'denConjunto', 'dtEntrada',
    ];

    private const OPERATION_FIELDS = [
        'operacao', 'descOperacao', 'inicioPlanejado', 'fimPlanejado',
        'inicioReal', 'fimReal', 'status', 'notaOv', 'cenPlan', 'cenTrab',
        'txtCenTrab',
    ];

    public function import(array $records): array
    {
        return DB::transaction(function () use ($records): array {
            $summary = [
                'records_received' => count($records),
                'records_created' => 0,
                'records_updated' => 0,
                'operations_created' => 0,
                'operations_updated' => 0,
                'items' => [],
            ];

            foreach ($records as $index => $record) {
                $result = $this->importRecord($record, (int) $index);
                $summary['records_created'] += $result['record_created'] ? 1 : 0;
                $summary['records_updated'] += $result['record_updated'] ? 1 : 0;
                $summary['operations_created'] += $result['operations_created'];
                $summary['operations_updated'] += $result['operations_updated'];
                $summary['items'][] = $result;
            }

            return $summary;
        });
    }

    private function importRecord(mixed $record, int $index): array
    {
        if (!is_array($record)) {
            throw new InvalidArgumentException("Registro {$index} deve ser um objeto JSON.");
        }

        [$noteNumber, $notePayload] = $this->noteData($record, $index);
        [$orderNumber, $orderPayload] = $this->orderData($record, $index);
        $operations = $this->operationsData($record['operacoes'] ?? $record['operations'] ?? [], $index);

        $this->assertAllowedFields($notePayload, self::NOTE_FIELDS, "notaEP do registro {$index}");
        $noteAttributes = $this->allowedAttributes($notePayload, self::NOTE_FIELDS, ['note']);
        $noteAttributes['note'] = $noteNumber;
        $noteAttributes['type_note'] ??= 1;
        $note = Note::query()->where('note', $noteNumber)->first();
        [$note, $noteCreated, $noteChanged] = $this->upsert($note, $noteAttributes, ['note' => $noteNumber]);

        $this->assertAllowedFields($orderPayload, self::ORDER_FIELDS, "ordem do registro {$index}");
        $orderAttributes = $this->allowedAttributes($orderPayload, self::ORDER_FIELDS, ['note_id', 'ordem']);
        $orderAttributes['note_id'] = $note->id;
        $orderAttributes['ordem'] = $orderNumber;
        [$order, $orderCreated, $orderChanged] = $this->upsert(
            Order::query()->where('note_id', $note->id)->where('ordem', $orderNumber)->first(),
            $orderAttributes,
            ['note_id' => $note->id, 'ordem' => $orderNumber]
        );

        $operationsCreated = 0;
        $operationsUpdated = 0;
        foreach ($operations as $operationPayload) {
            $operationNumber = $this->clean($operationPayload['operacao'] ?? null);
            if ($operationNumber === null) {
                throw new InvalidArgumentException("Registro {$index}: cada operação precisa informar operacao.");
            }

            $this->assertAllowedFields($operationPayload, self::OPERATION_FIELDS, "operação do registro {$index}");
            $operationAttributes = $this->allowedAttributes($operationPayload, self::OPERATION_FIELDS, ['order_id', 'operacao']);
            $operationAttributes['order_id'] = $order->id;
            $operationAttributes['operacao'] = $operationNumber;
            [, $created, $changed] = $this->upsert(
                Operation::query()->where('order_id', $order->id)->where('operacao', $operationNumber)->first(),
                $operationAttributes,
                ['order_id' => $order->id, 'operacao' => $operationNumber]
            );
            $operationsCreated += $created ? 1 : 0;
            $operationsUpdated += $changed ? 1 : 0;
        }

        return [
            'index' => $index,
            'notaEP' => $noteNumber,
            'ordem' => $orderNumber,
            'record_created' => $noteCreated || $orderCreated,
            'record_updated' => (!$noteCreated && $noteChanged) || (!$orderCreated && $orderChanged),
            'operations_received' => count($operations),
            'operations_created' => $operationsCreated,
            'operations_updated' => $operationsUpdated,
        ];
    }

    private function noteData(array $record, int $index): array
    {
        $raw = $record['notaEP'] ?? $record['notaEp'] ?? $record['nota_ep'] ?? $record['ovNota'] ?? null;
        $payload = is_array($raw) ? $raw : [];
        $value = is_array($raw) ? ($raw['note'] ?? $raw['notaEP'] ?? $raw['ovNota'] ?? null) : $raw;
        $value = $this->clean($value);
        if ($value === null) {
            throw new InvalidArgumentException("Registro {$index}: notaEP é obrigatório.");
        }

        return [$value, $payload];
    }

    private function orderData(array $record, int $index): array
    {
        $raw = $record['ordem'] ?? $record['order'] ?? null;
        $payload = is_array($raw) ? $raw : [];
        $value = is_array($raw) ? ($raw['ordem'] ?? $raw['order'] ?? $raw['number'] ?? null) : $raw;
        $value = $this->clean($value);
        if ($value === null) {
            throw new InvalidArgumentException("Registro {$index}: ordem é obrigatória.");
        }

        return [$value, $payload];
    }

    private function operationsData(mixed $raw, int $index): array
    {
        if ($raw === null || $raw === []) {
            return [];
        }

        $operations = array_is_list($raw) ? $raw : [$raw];
        foreach ($operations as $operation) {
            if (!is_array($operation)) {
                throw new InvalidArgumentException("Registro {$index}: operacoes deve ser um objeto ou array de objetos.");
            }
        }

        return $operations;
    }

    private function allowedAttributes(array $payload, array $fillable, array $excluded): array
    {
        return Arr::except(Arr::only($payload, $fillable), $excluded);
    }

    private function assertAllowedFields(array $payload, array $allowed, string $context): void
    {
        $unknown = array_values(array_diff(array_keys($payload), $allowed));

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                $context . ': campos não permitidos: ' . implode(', ', $unknown) . '.'
            );
        }
    }

    private function upsert(?object $existing, array $attributes, array $identity): array
    {
        if (!$existing) {
            $modelClass = match (true) {
                array_key_exists('note', $identity) => Note::class,
                array_key_exists('note_id', $identity) => Order::class,
                default => Operation::class,
            };
            return [$modelClass::create($attributes), true, false];
        }

        $changed = false;
        foreach ($attributes as $key => $value) {
            if ($this->comparable($existing->getRawOriginal($key)) !== $this->comparable($value)) {
                $changed = true;
                break;
            }
        }

        if ($changed) {
            $existing->fill($attributes);
            $existing->save();
        }

        return [$existing, false, $changed];
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' || strtoupper($value) === 'NULL' ? null : $value;
    }

    private function comparable(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        return trim((string) $value);
    }
}
