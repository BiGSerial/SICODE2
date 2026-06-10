<?php

namespace Tests\Feature;

use App\Models\{Bancoupdate, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenReturnInternReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_access_open_return_intern_report(): void
    {
        Bancoupdate::create([
            'last_update' => now(),
            'inserts'     => 0,
            'updates'     => 0,
            'error'       => 0,
        ]);

        $manager = User::factory()->create([
            'management'   => true,
            'superadm'     => false,
            'admin'        => false,
            'operator'     => false,
            'user'         => false,
            'contract'     => false,
            'engineer'     => false,
            'onlyparner'   => false,
            'responsible'  => false,
            'btzero'       => false,
            'can_dispatch' => false,
            'analyst'      => false,
        ]);

        $this->actingAs($manager)
            ->get(route('reports.return_intern'))
            ->assertOk()
            ->assertSee('Retorno Interno')
            ->assertSee('Todos os retornos internos abertos');
    }

    public function test_regular_user_cannot_access_open_return_intern_report(): void
    {
        $user = User::factory()->create([
            'management' => false,
            'superadm'   => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.return_intern'))
            ->assertForbidden();
    }
}
