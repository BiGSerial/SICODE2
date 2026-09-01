<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Analises\Stack as AnalysisStack;
use App\Http\Livewire\Dispatchs\AnalisesPre\Stack as PreAnalysisStack;
use App\Models\Company;
use App\Models\Note;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalysisStackOccupationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_analysis_stack_filters_by_occupation_user_click(): void
    {
        $this->assertStackFiltersByOccupationUser(AnalysisStack::class, 'analises');
    }

    public function test_pre_analysis_stack_filters_by_occupation_user_click(): void
    {
        $this->assertStackFiltersByOccupationUser(PreAnalysisStack::class, 'analises_pre');
    }

    private function assertStackFiltersByOccupationUser(string $component, string $filterGroup): void
    {
        $this->actingAs(User::factory()->create(['contract' => false]));

        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $targetUser = User::factory()->create(['contract' => false]);
        $otherUser = User::factory()->create(['contract' => false]);
        $service = Service::create(['service' => 'Analise', 'folder' => $filterGroup]);

        $targetProduction = $this->productionFor($service, $company, $targetUser, '4000000201');
        $this->productionFor($service, $company, $otherUser, '4000000202');

        $test = Livewire::test($component, ['service' => $service->uuid])
            ->set('selected', [$targetProduction->id + 1000])
            ->set('selectAll', true)
            ->call('filterUser', $targetUser->id)
            ->assertSet('user_fs', [(string) $targetUser->id])
            ->assertSet('selected', [])
            ->assertSet('selectAll', false);

        $this->assertSame([(string) $targetUser->id], session("filter.{$filterGroup}.user"));

        $lists = $test->instance()->getListsProperty();

        $this->assertSame([$targetProduction->id], $lists->pluck('id')->all());
    }

    private function productionFor(Service $service, Company $company, User $user, string $noteNumber): Production
    {
        $note = Note::create([
            'note' => $noteNumber,
            'dt_status' => now(),
            'dt_created' => now(),
            'nstats' => 'NEW',
            'type_note' => 1,
        ]);

        return Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'completed' => false,
            'confirmed' => false,
            'status' => 1,
            'dt_note' => now(),
            'status_note' => 'NEW',
        ]);
    }
}
