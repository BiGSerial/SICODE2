<?php

use App\Http\Livewire\Config\System\JobsServices;
use App\Models\User;
use App\Services\System\QueueMonitorService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Gate, Schema};
use Livewire\Livewire;

uses(RefreshDatabase::class);

// A tabela `jobs` não tem migration neste repositório (existe só nos ambientes); cria o mínimo para o teste.
beforeEach(function () {
    if (!Schema::hasTable('jobs')) {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    // O DDL acima confirma a transação do RefreshDatabase: limpa o que sobrar de outros testes.
    DB::table('jobs')->delete();
    DB::table('failed_jobs')->delete();
});

function qmJob(array $over = []): void
{
    DB::table('jobs')->insert($over + [
        'queue'        => 'default',
        'payload'      => json_encode(['displayName' => 'App\\Jobs\\FooJob']),
        'attempts'     => 0,
        'reserved_at'  => null,
        'available_at' => now()->timestamp - 30,
        'created_at'   => now()->timestamp - 30,
    ]);
}

function qmFailed(string $queue = 'default'): void
{
    DB::table('failed_jobs')->insert([
        'uuid'       => (string) Str::uuid(),
        'connection' => 'database',
        'queue'      => $queue,
        'payload'    => json_encode(['displayName' => 'App\\Jobs\\BarJob']),
        'exception'  => "RuntimeException: boom\n#0 stack",
        'failed_at'  => now(),
    ]);
}

it('classifica pendentes, em execução e agendados com contagem real por fila', function () {
    $now = now()->timestamp;
    qmJob();
    qmJob(['queue' => 'high']);
    qmJob(['reserved_at' => $now - 10]);
    qmJob(['reserved_at' => $now - 1000]);
    qmJob(['available_at' => $now + 3600]);
    qmFailed();

    $snap = app(QueueMonitorService::class)->snapshot($now);

    expect($snap['totals'])->toMatchArray(['pending' => 2, 'running' => 2, 'delayed' => 1, 'failed' => 1, 'long' => 1])
        ->and($snap['totals']['oldest_wait'])->toBe(30)
        ->and(collect($snap['queues'])->firstWhere('queue', 'high')['pending'])->toBe(1)
        ->and($snap['pending']['total'])->toBe(2)
        ->and($snap['failed']['rows']->first()->error_class)->toBe('RuntimeException')
        ->and($snap['failed']['rows']->first()->error)->toBe('boom');
});

it('exclui todos os falhados só depois da confirmação e só para superadm', function () {
    qmFailed();
    qmFailed('high');
    $user = User::factory()->create();
    Gate::define('superadm', fn () => true);

    Livewire::actingAs($user)->test(JobsServices::class)
        ->call('askDeleteAllFailed')->assertSet('confirmingDeleteAll', true)
        ->call('cancelDeleteAllFailed')->assertSet('confirmingDeleteAll', false);

    expect(DB::table('failed_jobs')->count())->toBe(2);

    Livewire::actingAs($user)->test(JobsServices::class)
        ->call('askDeleteAllFailed')->call('deleteAllFailed')->assertSet('confirmingDeleteAll', false);

    expect(DB::table('failed_jobs')->count())->toBe(0);
});

it('bloqueia excluir tudo sem superadm', function () {
    qmFailed();
    Gate::define('superadm', fn () => false);

    Livewire::actingAs(User::factory()->create())->test(JobsServices::class)->call('deleteAllFailed')->assertForbidden();

    expect(DB::table('failed_jobs')->count())->toBe(1);
});

it('reenfileira todos os falhados', function () {
    qmFailed();
    qmFailed();

    Livewire::actingAs(User::factory()->create())->test(JobsServices::class)->call('retryAllFailed');

    expect(DB::table('failed_jobs')->count())->toBe(0)->and(DB::table('jobs')->count())->toBe(2);
});

it('interpreta a saída do supervisorctl', function () {
    $row = app(QueueMonitorService::class)->parseSupervisorStatusLine('sicode-worker-high:sicode-worker-high_01   RUNNING   pid 542104, uptime 0:02:06');

    expect($row)->toMatchArray(['process' => 'sicode-worker-high_01', 'queue' => 'high', 'pid' => '542104', 'uptime' => '0:02:06', 'running' => true]);
});

it('formata durações em PT-BR', function () {
    expect(QueueMonitorService::human(45))->toBe('45 s')
        ->and(QueueMonitorService::human(180))->toBe('3 min')
        ->and(QueueMonitorService::human(3900))->toBe('1 h 05 min');
});
