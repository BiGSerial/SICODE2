<?php

namespace App\Services\Closure;

use App\Models\Closure\ClosureDepositCalendarEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Lê a planilha de inventário da gestão de ativos ("Inventário 2026") e grava o calendário de depósitos.
 * Reimportar a mesma planilha atualiza as linhas (chave: ano + centro + depósito) sem mexer em quem já foi
 * bloqueado, liberado ou dispensado.
 */
class ClosureDepositCalendarImporter
{
    /** cabeçalho normalizado => campo */
    private const COLUMNS = [
        'empresa da und'                          => 'company_label',
        'cidade'                                  => 'city',
        'centro'                                  => 'center',
        'deposito'                                => 'deposit',
        'denominacao'                             => 'denomination',
        'nome / tipo de servico'                  => 'service_type',
        'mes'                                     => 'month',
        'responsavel pela execucao do inventario' => 'inventory_responsible',
        'inventario realizado'                    => 'inventory_done',
        'data inicial das contagens'              => 'scheduled_date',
    ];

    private const REQUIRED = ['center', 'deposit', 'scheduled_date'];

    /** @return array{created: int, updated: int, kept: int, errors: array<int, string>, total: int} */
    public function import(string $path, User $actor): array
    {
        $summary = ['created' => 0, 'updated' => 0, 'kept' => 0, 'errors' => [], 'total' => 0];

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $rows  = $sheet->toArray(null, true, false, false);
        } catch (Throwable) {
            $summary['errors'][] = 'Não foi possível ler a planilha. Envie um arquivo .xlsx válido.';

            return $summary;
        }

        [$headerIndex, $map] = $this->locateHeader($rows);

        if ($map === null) {
            $summary['errors'][] = 'Cabeçalho não encontrado. A planilha precisa das colunas Centro, Depósito e Data inicial das contagens.';

            return $summary;
        }

        foreach (array_slice($rows, $headerIndex + 1, null, true) as $line => $row) {
            $data = [];

            foreach ($map as $column => $field) {
                $data[$field] = $row[$column] ?? null;
            }

            if ($this->isBlank($data)) {
                continue;
            }

            $summary['total']++;
            $number  = $line + 1;
            $center  = trim((string) ($data['center'] ?? ''));
            $deposit = trim((string) ($data['deposit'] ?? ''));
            $date    = $this->parseDate($data['scheduled_date'] ?? null, $data['month'] ?? null);

            if ($center === '' || $deposit === '' || !$date) {
                $summary['errors'][] = "Linha {$number}: centro, depósito e data inicial são obrigatórios" . ($deposit !== '' ? " (depósito {$deposit})" : '') . '.';

                continue;
            }

            $attributes = [
                'company_label'         => $this->text($data['company_label'] ?? null),
                'city'                  => $this->text($data['city'] ?? null),
                'denomination'          => $this->text($data['denomination'] ?? null),
                'service_type'          => $this->text($data['service_type'] ?? null),
                'month'                 => $date->month,
                'inventory_responsible' => $this->text($data['inventory_responsible'] ?? null),
                'inventory_done'        => Str::of((string) ($data['inventory_done'] ?? ''))->lower()->ascii()->trim()->exactly('sim'),
                'scheduled_date'        => $date->toDateString(),
            ];

            $entry = ClosureDepositCalendarEntry::query()->firstOrNew(['year' => $date->year, 'center' => $center, 'deposit' => $deposit]);

            if (!$entry->exists) {
                $entry->fill($attributes + ['status' => ClosureDepositCalendarEntry::STATUS_PENDING, 'imported_by' => $actor->id])->save();
                $summary['created']++;

                continue;
            }

            if ($entry->status !== ClosureDepositCalendarEntry::STATUS_PENDING) {
                $summary['kept']++; // já confirmado/liberado/dispensado: a data e o status não mudam

                continue;
            }

            $entry->fill($attributes + ['imported_by' => $actor->id])->save();
            $summary['updated']++;
        }

        return $summary;
    }

    /** @return array{0: int, 1: array<int, string>|null} */
    private function locateHeader(array $rows): array
    {
        foreach (array_slice($rows, 0, 40, true) as $index => $row) {
            $map = [];

            foreach ($row as $column => $value) {
                $key = $this->headerKey($value);

                if (isset(self::COLUMNS[$key])) {
                    $map[$column] = self::COLUMNS[$key];
                }
            }

            if (count(array_intersect(self::REQUIRED, $map)) === count(self::REQUIRED)) {
                return [$index, $map];
            }
        }

        return [0, null];
    }

    private function headerKey(mixed $value): string
    {
        return (string) Str::of((string) $value)->ascii()->lower()->squish();
    }

    private function isBlank(array $data): bool
    {
        return collect($data)->every(fn ($value) => $value === null || trim((string) $value) === '');
    }

    private function text(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /** Data serial do Excel, ou texto m/d/Y (formato da planilha de gestão de ativos) com d/m/Y de reserva. */
    private function parseDate(mixed $value, mixed $month): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            }

            $text = trim((string) $value);

            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $text, $m)) {
                $first         = (int) $m[1];
                $second        = (int) $m[2];
                $expectedMonth = is_numeric($month) ? (int) $month : null;

                // m/d/Y por padrão; se a coluna Mês contradiz e o inverso bate, usa d/m/Y.
                $asMonthFirst = $first <= 12 && ($expectedMonth === null || $expectedMonth === $first);
                $asDayFirst   = $second <= 12 && ($expectedMonth === null || $expectedMonth === $second);

                if ($asMonthFirst) {
                    return Carbon::create((int) $m[3], $first, $second)->startOfDay();
                }

                if ($asDayFirst) {
                    return Carbon::create((int) $m[3], $second, $first)->startOfDay();
                }

                return $first <= 12 ? Carbon::create((int) $m[3], $first, $second)->startOfDay() : null;
            }

            return Carbon::parse($text)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
