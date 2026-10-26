<?php

use App\Models\Closure\{ClosureIssueCategory, ClosureIssueSubcategory};
use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState, ClosureOrderStatePeriod};
use App\Models\{Company, Note, Order, Production, Service, User};
use App\Services\Closure\ClosureOrderWorkflowService;

/**
 * Fixture compartilhada pelos testes de tela da obra (não é um teste).
 *
 * @return array<string, mixed>
 */
function closureScreenFixture(string $status = 'IN_PROGRESS', string $statusSist = 'LIB CNPA'): array
{
    $flags   = ['superadm' => false, 'admin' => false, 'closure_manager' => false, 'closure_operator' => false];
    $service = Service::query()->firstOrCreate(
        ['folder' => 'encerramento'],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']
    );
    $company = Company::query()->create(['name' => 'Parceira Tela ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);
    $other   = Company::query()->create(['name' => 'Outra Tela ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);
    $note    = Note::query()->create(['note' => 'SC-' . fake()->unique()->numerify('######'), 'type_note' => 1]);
    $order   = Order::query()->create(['note_id' => $note->id, 'ordem' => fake()->unique()->numerify('400#########'), 'statusSist' => $statusSist]);
    $prod    = Production::query()->create(['note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id]);

    $hq = Company::query()->create(['name' => 'Interna ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);
    $flags += ['company_id' => $hq->id];
    $internal = User::factory()->create(array_merge($flags, ['closure_operator' => true]));
    $state    = ClosureOrderState::query()->create([
        'order_id'                  => $order->id,
        'production_id'             => $prod->id,
        'operational_status'        => $status,
        'assigned_user_id'          => $internal->id,
        'current_holder_type'       => 'INTERNAL_USER',
        'current_holder_user_id'    => $internal->id,
        'entered_current_status_at' => now()->subMinute(),
    ]);
    $event = ClosureOrderEvent::query()->create(['closure_order_state_id' => $state->id, 'order_id' => $order->id, 'type' => 'INITIALIZED', 'origin' => 'SYSTEM']);
    ClosureOrderStatePeriod::query()->create([
        'closure_order_state_id' => $state->id,
        'operational_status'     => $status,
        'holder_type'            => 'INTERNAL_USER',
        'holder_user_id'         => $internal->id,
        'started_at'             => $state->entered_current_status_at,
        'started_event_id'       => $event->id,
    ]);
    $state->update(['last_event_id' => $event->id]);

    // Segunda Ordem na pilha da parceira (dá à empresa acesso à obra).
    $partnerOrder = Order::query()->create(['note_id' => $note->id, 'ordem' => fake()->unique()->numerify('402#########'), 'statusSist' => $statusSist]);
    $partnerState = ClosureOrderState::query()->create([
        'order_id'                  => $partnerOrder->id,
        'production_id'             => $prod->id,
        'operational_status'        => 'AVAILABLE',
        'current_holder_type'       => 'PARTNER_COMPANY',
        'current_holder_company_id' => $company->id,
        'entered_current_status_at' => now(),
    ]);

    return [
        'note'         => $note,
        'order'        => $order,
        'state'        => $state,
        'partnerState' => $partnerState,
        'company'      => $company,
        'other'        => $other,
        'internal'     => $internal,
        'dispatcher'   => User::factory()->create(array_merge($flags, ['closure_manager' => true])),
        'partnerUser'  => User::factory()->create(array_merge($flags, ['company_id' => $company->id])),
        'outsider'     => User::factory()->create($flags),
    ];
}

if (!function_exists('closureWorkflowFixture')) {
    function closureWorkflowFixture(string $status = ClosureOrderWorkflowService::STATUS_AVAILABLE): array
    {
        $service = Service::query()->firstOrCreate(
            ['folder' => 'encerramento'],
            ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']
        );
        $company = Company::query()->create([
            'name'  => 'Parceira Workflow ' . fake()->unique()->numerify('###'),
            'email' => fake()->unique()->safeEmail(),
        ]);
        $actor      = User::factory()->create(['company_id' => $company->id]);
        $note       = Note::query()->create(['note' => 'WF-' . fake()->unique()->numerify('######'), 'type_note' => 1]);
        $order      = Order::query()->create(['note_id' => $note->id, 'ordem' => fake()->unique()->numerify('200#########'), 'statusSist' => 'LIB CNPA']);
        $production = Production::query()->create([
            'note_id'    => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
        ]);
        $state = ClosureOrderState::query()->create([
            'order_id'                  => $order->id,
            'production_id'             => $production->id,
            'operational_status'        => $status,
            'current_holder_type'       => ClosureOrderWorkflowService::HOLDER_PARTNER_COMPANY,
            'current_holder_company_id' => $company->id,
            'entered_current_status_at' => now()->subMinute(),
        ]);
        $event = ClosureOrderEvent::query()->create([
            'closure_order_state_id' => $state->id,
            'order_id'               => $order->id,
            'type'                   => 'INITIALIZED',
            'origin'                 => 'SYSTEM',
        ]);
        ClosureOrderStatePeriod::query()->create([
            'closure_order_state_id' => $state->id,
            'operational_status'     => $status,
            'holder_type'            => $state->current_holder_type,
            'holder_company_id'      => $company->id,
            'started_at'             => $state->entered_current_status_at,
            'started_event_id'       => $event->id,
        ]);
        $state->update(['last_event_id' => $event->id]);

        return compact('company', 'actor', 'order', 'production', 'state');
    }
}

if (!function_exists('closureCollabFixture')) {
    /**
         * Cenário de colaboração: uma obra (Nota) com uma Ordem, despachante, usuário interno
         * (Ordem atribuída), usuário da parceira que detém a Ordem e um usuário sem vínculo.
         *
         * @return array<string, mixed>
         */
    function closureCollabFixture(): array
    {
        $fixture = closureWorkflowFixture();
        $flags   = ['superadm' => false, 'admin' => false, 'closure_manager' => false, 'closure_operator' => false];

        $fixture['note']        = $fixture['order']->Note;
        $fixture['dispatcher']  = User::factory()->create(array_merge($flags, ['closure_manager' => true]));
        $fixture['internal']    = User::factory()->create(array_merge($flags, ['closure_operator' => true]));
        $fixture['partnerUser'] = User::factory()->create(array_merge($flags, ['company_id' => $fixture['company']->id]));
        $fixture['outsider']    = User::factory()->create($flags);
        $fixture['state']->update(['assigned_user_id' => $fixture['internal']->id]);

        return $fixture;
    }
}

if (!function_exists('closureCollabExtraOrder')) {
    /** Cria outra Ordem (com estado) na mesma Nota. */
    function closureCollabExtraOrder(array $fixture, string $status = 'AVAILABLE', string $statusSist = 'LIB CNPA'): ClosureOrderState
    {
        $order = Order::query()->create(['note_id' => $fixture['note']->id, 'ordem' => fake()->unique()->numerify('300#########'), 'statusSist' => $statusSist]);

        return ClosureOrderState::query()->create([
            'order_id'                  => $order->id,
            'production_id'             => $fixture['production']->id,
            'operational_status'        => $status,
            'current_holder_type'       => 'PARTNER_COMPANY',
            'current_holder_company_id' => $fixture['company']->id,
            'entered_current_status_at' => now(),
        ]);
    }
}

if (!function_exists('closureIssueTaxonomy')) {
    function closureIssueTaxonomy(bool $requiresDescription = false): array
    {
        $category    = ClosureIssueCategory::query()->create(['name' => 'Documento pendente ' . fake()->unique()->numerify('###')]);
        $subcategory = ClosureIssueSubcategory::query()->create([
            'closure_issue_category_id' => $category->id,
            'name'                      => 'Comprovante ' . fake()->unique()->numerify('###'),
            'requires_description'      => $requiresDescription,
        ]);

        return compact('category', 'subcategory');
    }
}

if (!function_exists('closureIssueFixture')) {
    function closureIssueFixture(bool $requiresDescription = false): array
    {
        ['company'  => $company, 'actor' => $actor, 'state' => $state] = closureWorkflowFixture();
        ['category' => $category, 'subcategory' => $subcategory]       = closureIssueTaxonomy($requiresDescription);
        $destination                                                   = Company::query()->create([
            'name'  => 'Destino pendência ' . fake()->unique()->numerify('###'),
            'email' => fake()->unique()->safeEmail(),
        ]);

        return compact('company', 'actor', 'state', 'category', 'subcategory', 'destination');
    }
}

if (!function_exists('closureWorkflowFixtureOrderInProduction')) {
    /** Outra Ordem na mesma produção/empresa de um fixture de tela, num status operacional dado. */
    function closureWorkflowFixtureOrderInProduction(array $f, string $status): array
    {
        $order = Order::query()->create(['note_id' => $f['note']->id, 'ordem' => fake()->unique()->numerify('500#########'), 'statusSist' => 'LIB CNPA']);
        $state = ClosureOrderState::query()->create([
            'order_id'                  => $order->id,
            'production_id'             => $f['state']->production_id,
            'operational_status'        => $status,
            'current_holder_type'       => 'INTERNAL_USER',
            'current_holder_user_id'    => $f['internal']->id,
            'assigned_user_id'          => $f['internal']->id,
            'entered_current_status_at' => now()->subMinute(),
        ]);

        return compact('order', 'state');
    }
}
