<?php

namespace App\Console\Commands\Tools;

use App\Models\{Adsform, Company, Note, Operation, Order, Production, User, WorkReport};
use App\Services\WorkReports\WorkReportCurrentStatusRefresher;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManageOperationalTestNote extends Command
{
    private const MARKER = 'SICODE-TESTE-FLOW';

    protected $signature = 'sicode:test-note-flow
        {action=create : create|inform|show|status|purge}
        {--note= : Numero da nota de teste}
        {--preset= : publication|publication-confirmed|fiscalization|fiscalization-start|payment|payment-start|closure|finalized|reset}
        {--op= : Operacao SAP para ajuste manual, ex. 0020,0030,0040,0050,0060}
        {--status= : Status da operacao no ajuste manual, ex. LIB, CNPA, CONF}
        {--start : Preenche inicioReal da operacao manual}
        {--finish : Preenche fimReal da operacao manual}
        {--order-status= : StatusSist das ordens associadas}
        {--scope=network : Escopo do informe final: network(Nota/170)|connection(Nota/180)|general(OV/200)}
        {--with-connection : Cria tambem uma ordem 180 de ligacao para nota com escopo de rede}
        {--force : Obrigatorio para purge}';

    protected $description = 'Cria, movimenta e remove uma nota generica para testar Fiscalizacao, Medicao e Publicacao por informe.';

    public function handle(WorkReportCurrentStatusRefresher $refresher): int
    {
        try {
            return match ((string) $this->argument('action')) {
                'create' => $this->createScenario($refresher),
                'inform' => $this->createAdditionalWorkReport($refresher),
                'show' => $this->showScenario(),
                'status' => $this->updateScenarioStatus($refresher),
                'purge' => $this->purgeScenario(),
                default => $this->failAction(),
            };
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'host.docker.internal')) {
                $this->error('Nao foi possivel conectar ao banco pelo host.docker.internal neste shell.');
                $this->line('Rode pelo container, por exemplo:');
                $this->line('docker exec sicode2-app php artisan sicode:test-note-flow ' . $this->argument('action') . $this->forwardedOptionsForDocker());

                return self::FAILURE;
            }

            throw $e;
        }
    }

    private function createScenario(WorkReportCurrentStatusRefresher $refresher): int
    {
        $noteNumber = $this->noteNumber();

        if (Note::where('note', $noteNumber)->exists()) {
            $this->error("A nota {$noteNumber} ja existe. Use outro --note ou rode purge com --force.");
            return self::FAILURE;
        }

        $company = $this->testCompany();
        $user = $this->testUser($company);
        $scope = $this->normalizedScope();

        $payload = DB::transaction(function () use ($noteNumber, $company, $user, $scope) {
            $note = Note::create([
                'note' => $noteNumber,
                'created_by' => self::MARKER,
                'dt_created' => now(),
                'dt_status' => now(),
                'user' => $user->name,
                'value' => 1000,
                'currency' => 'BRL',
                'client' => self::MARKER,
                'material' => self::MARKER . ' - NOTA PARA TESTE OPERACIONAL',
                'nstats' => '20',
                'status' => 'LIBERADA PARA TESTE',
                'days' => 0,
                'days_left' => 30,
                'rubrica' => 'TESTE',
                'type_note' => $scope === 'general' ? 2 : 1,
                'mmgd' => false,
                'doe' => false,
                'is45' => false,
                'mesalization' => false,
                'ma' => false,
            ]);

            $orders = $this->createOrders($note, $scope);

            $workReport = WorkReport::create([
                'note_id' => $note->id,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'date' => now()->toDateString(),
                'equipment' => true,
                'connection' => $scope === 'connection',
                'changes' => true,
                'damage' => false,
                'description' => self::MARKER . ' - informe final gerado para teste',
                'observation' => 'Cenario criado por comando artisan para testes operacionais.',
                'team' => 'TIME TESTE',
                'responsible' => $user->name,
                'approved' => true,
                'rejected' => false,
                'canceled' => false,
                'informer' => $user->name,
                'informed_at' => now(),
                'acceptance_accepted' => true,
                'acceptance_at' => now(),
                'acceptance_name' => $user->name,
                'acceptance_meta' => ['source' => self::MARKER],
                'selected_final_scopes' => $scope === 'general' ? null : [$scope],
            ]);

            $workReport->Orders()->sync($orders->pluck('id')->all());

            $ads = Adsform::create([
                'work_report_id' => $workReport->id,
                'note_id' => $note->id,
                'user_id' => $user->id,
                'name' => self::MARKER . ' ADS ficticia',
                'obs' => 'ADS ficticia criada para teste operacional.',
                'contract' => self::MARKER,
                'center' => 'TESTE',
                'deposit' => 'TESTE',
                'amount' => 123.45,
                'partial' => false,
                'tacit' => false,
            ]);

            return compact('note', 'workReport', 'orders', 'ads');
        });

        $refresher->refresh($payload['workReport']->id);

        $this->info('Cenario operacional criado.');
        $this->line("Nota: {$payload['note']->note}");
        $this->line("Informe: {$payload['workReport']->id}");
        $this->line('Ordens: ' . $payload['orders']->pluck('ordem')->implode(', '));
        $this->line("ADS: {$payload['ads']->id}");
        $this->newLine();
        $this->line("Proximo passo sugerido: php artisan sicode:test-note-flow show --note={$payload['note']->note}");

        return self::SUCCESS;
    }

    private function updateScenarioStatus(WorkReportCurrentStatusRefresher $refresher): int
    {
        $note = $this->testNoteOrFail();
        if (!$note) {
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($note) {
                if ($preset = $this->option('preset')) {
                    $this->applyPreset($note, (string) $preset);
                }

                if ($this->option('op')) {
                    $this->applyManualOperationUpdate($note);
                }

                if ($this->option('order-status')) {
                    $note->Orders()->update(['statusSist' => (string) $this->option('order-status')]);
                }
            });
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $note->load('WorkForm');
        if ($note->WorkForm) {
            $refresher->refresh($note->WorkForm->id);
        }

        $this->info("Status atualizado para a nota {$note->note}.");
        return $this->showScenario();
    }

    private function createAdditionalWorkReport(WorkReportCurrentStatusRefresher $refresher): int
    {
        $note = $this->testNoteOrFail();
        if (!$note) {
            return self::FAILURE;
        }

        $company = $this->testCompany();
        $user = $this->testUser($company);
        $scope = $this->normalizedScope();

        $payload = DB::transaction(function () use ($note, $company, $user, $scope) {
            $orders = $this->createOrders($note, $scope);

            $workReport = WorkReport::create([
                'note_id' => $note->id,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'date' => now()->toDateString(),
                'equipment' => true,
                'connection' => $scope === 'connection',
                'changes' => true,
                'damage' => false,
                'description' => self::MARKER . ' - informe adicional gerado para teste',
                'observation' => 'Informe adicional criado por comando artisan para testes operacionais.',
                'team' => 'TIME TESTE',
                'responsible' => $user->name,
                'approved' => true,
                'rejected' => false,
                'canceled' => false,
                'informer' => $user->name,
                'informed_at' => now(),
                'acceptance_accepted' => true,
                'acceptance_at' => now(),
                'acceptance_name' => $user->name,
                'acceptance_meta' => ['source' => self::MARKER, 'additional' => true],
                'selected_final_scopes' => $scope === 'general' ? null : [$scope],
            ]);

            $workReport->Orders()->sync($orders->pluck('id')->all());

            $ads = Adsform::create([
                'work_report_id' => $workReport->id,
                'note_id' => $note->id,
                'user_id' => $user->id,
                'name' => self::MARKER . ' ADS ficticia adicional',
                'obs' => 'ADS ficticia do informe adicional.',
                'contract' => self::MARKER,
                'center' => 'TESTE',
                'deposit' => 'TESTE',
                'amount' => 123.45,
                'partial' => false,
                'tacit' => false,
            ]);

            return compact('workReport', 'orders', 'ads');
        });

        $refresher->refresh($payload['workReport']->id);

        $this->info("Informe adicional criado para a nota {$note->note}.");
        $this->line("Informe: {$payload['workReport']->id}");
        $this->line('Escopo: ' . $scope);
        $this->line('Ordens: ' . $payload['orders']->pluck('ordem')->implode(', '));
        $this->line("ADS: {$payload['ads']->id}");

        return self::SUCCESS;
    }

    private function showScenario(): int
    {
        $note = $this->testNoteOrFail();
        if (!$note) {
            return self::FAILURE;
        }

        $note->load(['WorkForm.Adsform', 'WorkForm.Orders.Operations', 'Adsform']);

        $this->info("Nota: {$note->note} (#{$note->id})");
        $this->line("Marcador: {$note->created_by} | Status nota: {$note->nstats} / {$note->status}");

        if ($note->WorkForm) {
            $this->line("Informe final: {$note->WorkForm->id} | status atual: {$note->WorkForm->current_status_label}");
            $this->line('Escopos selecionados: ' . json_encode($note->WorkForm->selected_final_scopes));
            $this->line('ADS: ' . ($note->WorkForm->Adsform?->id ?? 'sem ADS'));
        }

        foreach ($note->WorkForm?->Orders ?? $note->Orders as $order) {
            $this->line('');
            $this->line("Ordem {$order->ordem} | statusSist={$order->statusSist}");
            foreach ($order->Operations->sortBy('operacao') as $operation) {
                $this->line(sprintf(
                    '  OP%s %-8s inicio=%s fim=%s',
                    $operation->operacao,
                    $operation->status,
                    $operation->inicioReal?->format('Y-m-d') ?? '-',
                    $operation->fimReal?->format('Y-m-d') ?? '-'
                ));
            }
        }

        return self::SUCCESS;
    }

    private function purgeScenario(): int
    {
        $note = $this->testNoteOrFail();
        if (!$note) {
            return self::FAILURE;
        }

        if (!$this->option('force')) {
            $this->error('Use --force para remover o cenario de teste.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($note) {
            $note->load(['Orders', 'WorkForm', 'Productions']);

            $orderIds = $note->Orders->pluck('id');
            $workReportIds = WorkReport::where('note_id', $note->id)->pluck('id');
            $productionIds = Production::where('note_id', $note->id)->pluck('id');

            DB::table('work_report_flow_productions')->whereIn('work_report_id', $workReportIds)->delete();
            DB::table('note_inform_flows')->where('note_id', $note->id)->delete();
            DB::table('wpas')->where('note_id', $note->id)->orWhereIn('production_id', $productionIds)->delete();
            DB::table('notetimelines')
                ->where('note_id', $note->id)
                ->orWhereIn('note_id', $productionIds)
                ->orWhereIn('production_id', $productionIds)
                ->delete();
            DB::table('adsforms_files')
                ->whereIn('adsform_id', Adsform::whereIn('work_report_id', $workReportIds)->pluck('id'))
                ->delete();
            Adsform::whereIn('work_report_id', $workReportIds)->delete();
            Production::whereIn('id', $productionIds)->delete();
            DB::table('order_work_report')->whereIn('work_report_id', $workReportIds)->orWhereIn('order_id', $orderIds)->delete();
            Operation::whereIn('order_id', $orderIds)->delete();
            WorkReport::whereIn('id', $workReportIds)->delete();
            Order::whereIn('id', $orderIds)->delete();
            $note->delete();
        });

        $this->info("Cenario da nota {$note->note} removido.");
        return self::SUCCESS;
    }

    private function createOrders(Note $note, string $scope)
    {
        $prefixes = match ($scope) {
            'connection' => ['180'],
            'general' => ['200'],
            default => ['170'],
        };

        if ($this->option('with-connection') && !in_array('180', $prefixes, true)) {
            $prefixes[] = '180';
        }

        return collect($prefixes)->map(function (string $prefix, int $index) use ($note) {
            $order = Order::create([
                'note_id' => $note->id,
                'ordem' => $this->orderNumber($note, $prefix, $index),
                'descricao' => self::MARKER . " - ordem {$prefix}",
                'locInstalacao' => 'TESTE',
                'cenPlan' => 'TESTE',
                'prioridade' => '3',
                'statusSist' => 'LIB',
                'statusUser' => 'LIB',
                'cenTrab' => 'TESTE',
                'gpm' => 'TESTE',
                'pep' => $note->pep,
                'dtEntrada' => now(),
            ]);

            foreach (['0010', '0020', '0030', '0040', '0050', '0060'] as $operation) {
                Operation::create([
                    'order_id' => $order->id,
                    'operacao' => $operation,
                    'descOperacao' => self::MARKER . " - OP{$operation}",
                    'inicioPlanejado' => now(),
                    'fimPlanejado' => now()->addDay(),
                    'status' => 'LIB',
                    'notaOv' => $note->note,
                    'cenPlan' => 'TESTE',
                    'cenTrab' => 'TESTE',
                    'txtCenTrab' => self::MARKER,
                ]);
            }

            return $order;
        });
    }

    private function applyPreset(Note $note, string $preset): void
    {
        $this->resetOperations($note);

        match ($preset) {
            'reset', 'publication', 'fiscalization' => null,
            'publication-confirmed' => $this->finishOperations($note, ['0020']),
            'fiscalization-start' => $this->startOperation($note, '0030', 'CNPA'),
            'payment' => $this->finishOperations($note, ['0030']),
            'payment-start' => $this->startOperationAfter($note, '0040', ['0030']),
            'closure' => $this->finishOperations($note, ['0030', '0040', '0050']),
            'finalized' => $this->finalizeAll($note),
            default => throw new \InvalidArgumentException("Preset invalido: {$preset}"),
        };
    }

    private function resetOperations(Note $note): void
    {
        $note->Orders()->update(['statusSist' => 'LIB', 'statusUser' => 'LIB']);
        Operation::whereIn('order_id', $note->Orders()->pluck('id'))->update([
            'status' => 'LIB',
            'inicioReal' => null,
            'fimReal' => null,
        ]);
    }

    private function finishOperations(Note $note, array $operations): void
    {
        Operation::whereIn('order_id', $note->Orders()->pluck('id'))
            ->whereIn('operacao', $operations)
            ->update([
                'status' => 'CONF',
                'inicioReal' => now()->subDay(),
                'fimReal' => now(),
            ]);
    }

    private function startOperation(Note $note, string $operation, string $status): void
    {
        Operation::whereIn('order_id', $note->Orders()->pluck('id'))
            ->where('operacao', $operation)
            ->update([
                'status' => $status,
                'inicioReal' => now(),
                'fimReal' => null,
            ]);
    }

    private function startOperationAfter(Note $note, string $operation, array $finished): void
    {
        $this->finishOperations($note, $finished);
        $this->startOperation($note, $operation, 'CNPA');
    }

    private function finalizeAll(Note $note): void
    {
        $this->finishOperations($note, ['0030', '0040', '0050', '0060']);
        $note->Orders()->update(['statusSist' => 'ENCE', 'statusUser' => 'ENCE']);
    }

    private function applyManualOperationUpdate(Note $note): void
    {
        $updates = [];

        if ($this->option('status')) {
            $updates['status'] = (string) $this->option('status');
        }

        if ($this->option('start')) {
            $updates['inicioReal'] = now();
        }

        if ($this->option('finish')) {
            $updates['inicioReal'] = $updates['inicioReal'] ?? now()->subDay();
            $updates['fimReal'] = now();
        }

        if (empty($updates)) {
            return;
        }

        Operation::whereIn('order_id', $note->Orders()->pluck('id'))
            ->where('operacao', (string) $this->option('op'))
            ->update($updates);
    }

    private function testCompany(): Company
    {
        return Company::firstOrCreate(
            ['email' => 'teste-flow@sicode.local'],
            ['name' => self::MARKER . ' EMPRESA', 'telephone' => '000000000']
        );
    }

    private function testUser(Company $company): User
    {
        return User::firstOrCreate(
            ['email' => 'teste-flow@sicode.local'],
            [
                'name' => self::MARKER . ' USUARIO',
                'Registration' => 'TESTEFLOW',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'user' => true,
                'operator' => true,
                'can_dispatch' => true,
            ]
        );
    }

    private function testNoteOrFail(): ?Note
    {
        $noteNumber = $this->option('note');
        if (!$noteNumber) {
            $this->error('Informe --note.');
            return null;
        }

        $note = Note::where('note', $noteNumber)->first();
        if (!$note) {
            $this->error("Nota {$noteNumber} nao encontrada.");
            return null;
        }

        if (!$this->isTestNote($note)) {
            $this->error("A nota {$noteNumber} nao foi criada por este comando. Operacao bloqueada.");
            return null;
        }

        return $note;
    }

    private function isTestNote(Note $note): bool
    {
        return str_starts_with((string) $note->note, '990')
            && (
                (string) $note->created_by === self::MARKER
                || str_contains((string) $note->material, self::MARKER)
                || str_contains((string) $note->client, self::MARKER)
            );
    }

    private function noteNumber(): string
    {
        if ($this->option('note')) {
            return (string) $this->option('note');
        }

        do {
            $note = '990' . random_int(1000000, 9999999);
        } while (Note::where('note', $note)->exists());

        return $note;
    }

    private function orderNumber(Note $note, string $prefix, int $index): string
    {
        $noteSuffix = substr(str_pad((string) $note->note, 10, '0', STR_PAD_LEFT), -7);
        $sequence = $index + 1;

        do {
            $number = $prefix . $noteSuffix . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Order::where('ordem', $number)->exists());

        return $number;
    }

    private function normalizedScope(): string
    {
        $scope = (string) $this->option('scope');

        return in_array($scope, ['network', 'connection', 'general'], true) ? $scope : 'network';
    }

    private function failAction(): int
    {
        $this->error('Acao invalida. Use create, inform, show, status ou purge.');
        return self::FAILURE;
    }

    private function forwardedOptionsForDocker(): string
    {
        $options = collect([
            'note',
            'preset',
            'op',
            'status',
            'order-status',
            'scope',
        ])
            ->map(function (string $option) {
                $value = $this->option($option);

                return $value === null || $value === false || $value === ''
                    ? null
                    : " --{$option}=" . escapeshellarg((string) $value);
            })
            ->filter()
            ->implode('');

        foreach (['start', 'finish', 'with-connection', 'force'] as $flag) {
            if ($this->option($flag)) {
                $options .= " --{$flag}";
            }
        }

        return $options;
    }
}
