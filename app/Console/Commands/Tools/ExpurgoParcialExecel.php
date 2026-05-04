<?php

namespace App\Console\Commands\Tools;

use App\Console\Commands\Concerns\ShowsProgress;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AdsImportRaw;
use App\Models\edp_cipqa\OldAdsList;
use App\Models\OldAdsInform;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExpurgoParcialExecel extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:exporgoParcialExecel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $directory = 'uploads'; // Diretório onde os arquivos Excel estão
        $files = Storage::disk('local')->files($directory);
        $excelFiles = array_filter($files, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'xlsx'; // Filtra apenas arquivos .xlsx
        });

        if (empty($excelFiles)) {
            $this->info('No Excel files found in the uploads directory.');
            return 0;
        }

        $allAdsData = []; // Array para armazenar todos os dados

        foreach ($excelFiles as $file) {
            $filePath = Storage::path($file);

            $this->info("Reading data from: " . $file);

            try {
                $adsData = Excel::toArray(new AdsImportRaw(), $filePath); // Lê os dados para um array

                // Itera sobre cada planilha (se houver várias)
                foreach ($adsData as $sheetData) {
                    // Itera sobre cada linha da planilha
                    foreach ($sheetData as $row) {
                        // Formata as datas se existirem e forem objetos Carbon
                        $excelSerialDate = $row['hora_de_conclusao'];

                        // Converter a data serial do Excel para um timestamp Unix
                        $unixTimestamp = ($excelSerialDate - 25569) * 86400;  // Fórmula para Excel (data base 1900)

                        // Criar um objeto Carbon a partir do timestamp Unix
                        if ($unixTimestamp > 0) {  //Verifica se a conversão é válida
                            $carbonDate = Carbon::createFromTimestamp($unixTimestamp)->addHours(3)->format('Y-m-d H:i:s');
                            $row['hora_de_conclusao'] = $carbonDate;  // Substitui o valor decimal pelo objeto Carbon
                        } else {
                            $row['hora_de_conclusao'] = null; // Ou outro valor padrão, se a conversão falhar
                        }

                        // Adiciona a linha formatada ao array principal
                        $allAdsData[] = $row;
                    }
                }

                $this->info("Successfully read data from: " . $file);

            } catch (\Exception $e) {
                $this->error("Error reading data from " . $file . ": " . $e->getMessage());
            }
        }

        // Verifica se os dados foram lidos corretamente

        // Cria a barra de progresso
        $totalRows = count($allAdsData);
        $progressBar = $this->createProgressBar($totalRows);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%'); // Formatação da barra
        $progressBar->start();

        $batchSize = 200;
        $parcialData = [];
        $finalData = [];

        if ($allAdsData) {


            foreach ($allAdsData as $data) {

                if ($data['ov'] != null && $data['tipo_execucao'] == 'PARCIAL') {
                    $parcialData['ov'][] = $data['ov'];
                } elseif ($data['ov'] != null && $data['tipo_execucao'] == 'TOTAL') {
                    $finalData['ov'][] = $data['ov'];
                }

                if ($data['nota'] != null && $data['tipo_execucao'] == 'PARCIAL') {
                    $parcialData['nota'][] = $data['nota'];
                } elseif ($data['nota'] != null && $data['tipo_execucao'] == 'TOTAL') {
                    $finalData['nota'][] = $data['nota'];
                }
                $progressBar->advance();


            }

            // Verifica se há e quais valores de $parcialData['ov'] existem em $finalData['ov']
            $parcialOv = $parcialData['ov'] ?? [];
            $finalOv = $finalData['ov'] ?? [];
            $diffOv = array_diff($parcialOv, $finalOv);
            $diffOv = array_values($diffOv); // Reindexa o array

            $parcialOv = $parcialData['ov'] ?? [];
            $finalOv = $finalData['ov'] ?? [];
            $diffNote = array_diff($parcialOv, $finalOv);
            $diffNote = array_values($diffNote); // Reindexa o array

            // Juntar os arrays $diffOv e $diffNote
            $combined = array_merge($diffOv, $diffNote);
            $combined = array_unique($combined);
            $combined = array_values($combined);



            // Insere qualquer registro restante que não completou um lote
            // if (count($diffNote) > 0 || count($diffOv) > 0) {
            //     $count = OldAdsList::where(function ($q) use ($diffOv, $diffNote) {
            //         $q->orWhereIn('Ov', $diffOv)->orWhereIn('Nota', $diffNote);
            //     })->count();
            // }

            $count = OldAdsInform::whereRelation('Note', function ($q) use ($combined) {
                $q->whereIn('note', $combined);


            })->delete();

            dd($count);


        }

        $progressBar->finish(); // Finaliza a barra de progresso
        $this->info("\nImportação concluída!");

        $this->info('Reading process completed.');
        return 0;
    }
}
