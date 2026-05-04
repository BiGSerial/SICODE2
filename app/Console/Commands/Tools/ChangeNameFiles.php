<?php

namespace App\Console\Commands\Tools;

use App\Console\Commands\Concerns\ShowsProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\File;

class ChangeNameFiles extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:sync-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza os nomes dos arquivos físicos com os nomes registrados no banco de dados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando a verificação e sincronização dos arquivos...');

        // Total de registros para a barra de progresso
        $totalFiles = File::count();

        if ($totalFiles === 0) {
            $this->info('Nenhum arquivo encontrado no banco de dados.');
            return Command::SUCCESS;
        }

        // Inicializa a barra de progresso
        $this->progressStart($totalFiles);

        // Inicializa contadores
        $ignored = 0;
        $updated = 0;
        $errors = 0;

        // Processa os registros em chunks
        File::chunk(500, function ($files) use (&$ignored, &$updated, &$errors) {
            foreach ($files as $file) {
                $currentPath = $file->path; // Caminho completo do arquivo no disco
                $expectedFileName = $file->file_name . '.' . $file->ext; // Nome esperado do arquivo com extensão
                $directory = dirname($currentPath); // Diretório onde o arquivo está armazenado
                $expectedPath = $directory . '/' . $expectedFileName; // Caminho esperado

                // Verificar se o arquivo físico existe no caminho atual
                if (!Storage::exists($currentPath)) {
                    // $this->warn("Arquivo não encontrado: $currentPath. Ignorando...");
                    $ignored++;
                    $this->progressAdvance();
                    continue;
                }

                // Verificar se o nome do arquivo físico está correto
                if ($currentPath !== $expectedPath) {
                    // Renomear o arquivo físico
                    if (!Storage::move($currentPath, $expectedPath)) {
                        $this->error("Erro ao renomear o arquivo: $currentPath");
                        $errors++;
                        $this->progressAdvance();
                        continue;
                    }

                    // Atualizar o caminho no banco de dados
                    $file->path = $expectedPath;
                    $file->save();
                    $updated++;
                }

                // Avança a barra de progresso
                $this->progressAdvance();
            }
        });

        // Finaliza a barra de progresso
        $this->progressFinish();

        // Exibe o resumo
        $this->info("Sincronização concluída!");
        $this->info("Arquivos ignorados: $ignored");
        $this->info("Arquivos atualizados: $updated");
        $this->info("Erros encontrados: $errors");

        return Command::SUCCESS;
    }
}
