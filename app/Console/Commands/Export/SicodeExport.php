<?php

namespace App\Console\Commands\Export;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SicodeExport extends Command
{
    /**
     * Nome e assinatura do comando
     *
     * Exemplos:
     *  php artisan sicode:export --fuser=Samuel
     *  php artisan sicode:export --tables=users,productions --user=UUID --extract
     */
    protected $signature = 'sicode:export
                            {--tables= : Lista de tabelas separadas por vírgula}
                            {--user= : ID do usuário que receberá a notificação}
                            {--fuser= : Filtro por nome do usuário, modo de busca}
                            {--extract : Quando presente, gera o arquivo Excel e envia o link}';

    /**
     * Descrição do comando
     */
    protected $description = 'Exporta dados de tabelas específicas para Excel e notifica um usuário com o link do arquivo';

    public function handle(): int
    {
        $filterName = (string) $this->option('fuser');
        $doExtract  = (bool) $this->option('extract');

        /**
         * Modo 1: busca de usuário
         * php artisan sicode:export --fuser=Samuel
         */
        if ($filterName && ! $doExtract) {
            return $this->searchUsersByName($filterName);
        }

        /**
         * Se não é busca e também não pediu --extract, avisa
         */
        if (! $doExtract) {
            $this->error('Use --extract para gerar o arquivo ou --fuser para apenas buscar usuários.');
            return self::FAILURE;
        }

        /**
         * Modo 2: export de tabelas e notificação
         * php artisan sicode:export --tables=users,productions --user=UUID --extract
         */
        $tablesOption = $this->option('tables');

        if (! $tablesOption) {
            $this->error('Informe ao menos uma tabela em --tables=users,productions,...');
            return self::FAILURE;
        }

        $tables = collect(explode(',', $tablesOption))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($tables)) {
            $this->error('Nenhuma tabela válida informada em --tables.');
            return self::FAILURE;
        }

        // Valida se as tabelas existem
        $schema = DB::getSchemaBuilder();
        $validTables = [];

        foreach ($tables as $table) {
            if (! $schema->hasTable($table)) {
                $this->warn("Tabela '{$table}' não existe. Ignorando.");
            } else {
                $validTables[] = $table;
            }
        }

        if (empty($validTables)) {
            $this->error('Nenhuma tabela existente após validação.');
            return self::FAILURE;
        }

        // Usuário que vai receber o link por notificação
        $userId = $this->option('user');
        $user   = null;

        if ($userId) {
            $user = User::find($userId);

            if (! $user) {
                $this->error("Usuário com id '{$userId}' não foi encontrado.");
                return self::FAILURE;
            }
        }

        // Monta sufixo com nomes das tabelas (para o nome do arquivo)
        $suffix = implode('_', $validTables);

        // Padrão de path, mantendo sua ideia
        $filePath = 'exports/' . now()->format('YmdHis') . "_{$suffix}_dispatch_export.xlsx";

        $this->info('Gerando arquivo: ' . $filePath);

        // Gera o Excel com múltiplas abas, uma por tabela
        Excel::store(
            new MultiTableExport($validTables),
            $filePath,
            'local'
        );

        if (! Storage::disk('local')->exists($filePath)) {
            throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
        }

        $this->info('Arquivo gerado com sucesso!');

        if ($user) {
            $url = Storage::url($filePath);

            $user->notify(new SystemNotification(
                'Exportação concluída!',
                'Seu relatório está pronto para download.',
                $url,
                4,
                []
            ));

            $this->info("Notificação enviada para {$user->name} ({$user->email}).");
        } else {
            $this->warn('Nenhum usuário informado em --user, então nenhuma notificação foi enviada.');
        }

        return self::SUCCESS;
    }

    /**
     * Modo de busca de usuário por nome
     *
     * Exemplo:
     *   php artisan sicode:export --fuser=Samuel
     */
    protected function searchUsersByName(string $name): int
    {
        $users = User::query()
            ->where('name', 'like', "%{$name}%")
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email']);

        if ($users->isEmpty()) {
            $this->warn("Nenhum usuário encontrado contendo '{$name}' no nome.");
            return self::SUCCESS;
        }

        $this->info('Usuários encontrados:');
        foreach ($users as $user) {
            $this->line("{$user->id} - {$user->name} <{$user->email}>");
        }

        $this->line('');
        $this->line('Use o ID desejado em:');
        $this->line('php artisan sicode:export --tables=users,productions --user=<id> --extract');

        return self::SUCCESS;
    }
}
