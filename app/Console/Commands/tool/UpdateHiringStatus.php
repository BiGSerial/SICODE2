<?php

namespace App\Console\Commands\tool;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\SicodeSql\HiringStatus;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class UpdateHiringStatus extends Command
{
    use ShowsProgress;

    protected $signature = 'hiringstatus:update
                            {file=toHiringConsolidado-20250422-1405.xlsx : Arquivo Excel em storage/app/uploads}';

    protected $description = 'Importa/atualiza registros em HiringStatus a partir de um Excel em storage/app/uploads';

    public function handle()
    {
        $file = $this->argument('file');
        $path = storage_path("app/uploads/{$file}");

        if (! file_exists($path)) {
            $this->error("Arquivo não encontrado em: {$path}");
            return 1;
        }

        $this->info("Iniciando importação de: {$path}");

        // Carrega todas as linhas (incluindo o cabeçalho)
        $collection = Excel::toCollection(null, $path)->first();

        // 1) Extrai cabeçalho (linha 0)
        $header = $collection->first()->toArray();

        // 2) Remove o cabeçalho e pega só as linhas de dados
        $dataRows = $collection->slice(1);

        $this->progressStart($dataRows->count());

        foreach ($dataRows as $row) {
            // 3) Combina cabeçalho + valores em array associativo
            $values = $row->toArray();
            $data = array_combine($header, $values);



            // Ajustes de tipo
            $data['tacit'] = (bool) Arr::get($data, 'tacit', false);


            // Ajusta 'tacit_at', ignorando a string "NULL"
            $rawTacitAt = Arr::get($data, 'tacit_at');
            if ($rawTacitAt && strtoupper($rawTacitAt) !== 'NULL') {
                $data['tacit_at'] = Carbon::parse($rawTacitAt);
            } else {
                $data['tacit_at'] = null;
            }

            // Mesmos cuidados podem ser repetidos para dt_status e last_date, se necessário
            $rawDtStatus = Arr::get($data, 'dt_status');
            if ($rawDtStatus && strtoupper($rawDtStatus) !== 'NULL') {
                $data['dt_status'] = Carbon::parse($rawDtStatus);
            }

            $rawLastDate = Arr::get($data, 'last_date');
            if (strtoupper($rawLastDate) === 'NULL') {
                continue;
            }

            if ($rawLastDate && strtoupper($rawLastDate) !== 'NULL') {
                $data['last_date'] = Carbon::parse($rawLastDate);
            } else {
                $data['last_date'] = null;
            }

            // Atualiza ou cria pelo note_id
            HiringStatus::updateOrCreate(
                ['note_id' => Arr::get($data, 'note_id')],
                [
                    'note'        => Arr::get($data, 'note'),
                    'dt_status'   => $data['dt_status'] ?? null,
                    'last_date'   => $data['last_date'] ?? null,
                    'position'    => Arr::get($data, 'position'),
                    'register'    => Arr::get($data, 'register'),
                    'responsible' => Arr::get($data, 'responsible'),
                    'tacit'       => $data['tacit'],
                ]
            );

            $this->progressAdvance();
        }

        $this->progressFinish();
        $this->info('Importação concluída com sucesso!');

        return 0;
    }
}
