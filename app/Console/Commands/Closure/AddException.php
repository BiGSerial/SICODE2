<?php

namespace App\Console\Commands\Closure;

use App\Models\{ClosureCycle, Order};
use App\Services\Closure\ClosureExceptionService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class AddException extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closure:add-exception
        {order : ID da Ordem (orders.id)}
        {--cycle= : Competência de destino, formato AAAA-MM (criada se ainda não existir)}
        {--reason= : Justificativa da exceção (obrigatória)}
        {--authorized-by= : ID do usuário (superior) que autorizou a exceção (obrigatório)}
        {--requested-by= : ID do usuário que solicitou a exceção (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registra uma Ordem como caso atípico (exceção manual) em uma competência de '
        . 'encerramento, inclusive se ela já estiver travada (FROZEN). Processo não automático: só roda '
        . 'sob solicitação/autorização superior e justificativa.';

    public function handle(ClosureExceptionService $service): int
    {
        $order = Order::find($this->argument('order'));

        if (!$order) {
            $this->error('Ordem não encontrada.');

            return self::FAILURE;
        }

        $cycleLabel = $this->option('cycle');

        if (!$cycleLabel) {
            $this->error('Informe --cycle=AAAA-MM (competência de destino da exceção).');

            return self::FAILURE;
        }

        [$year, $month] = explode('-', $cycleLabel);

        $cycle = ClosureCycle::firstOrCreate(
            ['year' => (int) $year, 'month' => (int) $month],
            ['label' => $cycleLabel, 'status' => ClosureCycle::STATUS_OPEN]
        );

        try {
            $target = $service->registerException(
                $order,
                $cycle,
                (string) $this->option('reason'),
                $this->option('authorized-by'),
                $this->option('requested-by')
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $statusNote = $cycle->status === ClosureCycle::STATUS_FROZEN ? ' (competência já estava travada — exceção aplicada mesmo assim)' : '';
        $this->info("Ordem {$order->ordem} registrada como EXCEÇÃO na competência {$cycle->label}{$statusNote}. closure_target #{$target->id}.");

        return self::SUCCESS;
    }
}
