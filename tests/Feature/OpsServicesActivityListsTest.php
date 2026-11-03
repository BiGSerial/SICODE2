<?php

namespace Tests\Feature;

use App\Http\Livewire\Services\Analisesinc\Main as AnalisesincMain;
use App\Http\Livewire\Services\Cadastro\Main as CadastroMain;
use App\Http\Livewire\Services\Incorporation\Main as IncorporationMain;
use App\Http\Livewire\Services\Oexterno\Main as OexternoMain;
use App\Models\{Note, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Listas de Serviços (Incorporação, Análise (inc), Cadastro, Entidade Externa) no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
class OpsServicesActivityListsTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWS = [
        'incorporation/main',
        'analisesinc/main',
        'cadastro/main',
        'oexterno/main',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();

        $this->actingAs(User::factory()->create(['contract' => false]));
    }

    public static function activityLists(): array
    {
        return [
            'Incorporação'  => [IncorporationMain::class, 'Incorporação', 'incorporation'],
            'Análise (inc)' => [AnalisesincMain::class, 'Análise (inc)', 'analises_inc'],
            'Cadastro'      => [CadastroMain::class, 'Cadastro', 'cadastro'],
            'Ent. Externa'  => [OexternoMain::class, 'Entidade Externa', 'oexterno'],
        ];
    }

    /** @dataProvider activityLists */
    public function test_list_renders_empty_filters_and_clears(string $component, string $name, string $folder): void
    {
        $service = Service::create(['service' => $name, 'folder' => $folder]);

        Livewire::test($component, ['service' => $service->uuid])
            ->assertStatus(200)
            ->assertSee('Serviços · ' . $name);
    }

    /** @dataProvider activityLists */
    public function test_list_applies_search_and_clear_all(string $component, string $name, string $folder): void
    {
        $service = Service::create(['service' => $name, 'folder' => $folder]);
        Note::create(['note' => '4000099001', 'dt_status' => now(), 'dt_created' => now(), 'rubrica' => 'Mercado']);

        $test = Livewire::test($component, ['service' => $service->uuid])
            ->set('search', '4000099001')
            ->assertStatus(200)
            ->call('applyListFilters', ['rubrica' => ['Inexistente']])
            ->assertSet('filters', [])
            ->call('clearAllCriteria')
            ->assertSet('search', '')
            ->assertSet('filters', []);

        $this->assertFalse($test->instance()->hasListFilters());
    }

    public function test_oexterno_lists_note_to_protocol_and_applies_type_and_rubrica_filter(): void
    {
        $service = Service::create(['service' => 'Entidade Externa', 'folder' => 'oexterno']);
        Note::create(['note' => '4000099002', 'dt_status' => now()->subDays(70), 'dt_created' => now()->subDays(40), 'nstats' => 20, 'type_note' => 1, 'rubrica' => 'Mercado']);
        Note::create(['note' => '4000099003', 'dt_status' => now(), 'dt_created' => now(), 'nstats' => 11, 'type_note' => 2, 'rubrica' => 'Outra']);

        Livewire::test(OexternoMain::class, ['service' => $service->uuid])
            ->assertSee('4000099002')
            ->assertSee('4000099003')
            ->assertSee('A PROTOCOLAR')
            ->set('statusFilter', '20')
            ->assertSee('4000099002')
            ->assertDontSee('4000099003')
            ->call('clearAllCriteria')
            ->set('typeNote', '2')
            ->assertDontSee('4000099002')
            ->assertSee('4000099003')
            ->call('clearAllCriteria')
            ->assertSet('typeNote', '')
            ->assertSet('statusFilter', '');
    }

    public function test_views_follow_the_pattern_without_inline_style_or_legacy_markup(): void
    {
        foreach (self::VIEWS as $view) {
            $html = file_get_contents(resource_path("views/livewire/services/{$view}.blade.php"));

            $this->assertStringContainsString('closure-dash my-panel ops-list', $html, $view);
            $this->assertDoesNotMatchRegularExpression('/<style|<script|@push|text-bg-|table-(?:danger|warning|success|info|primary|dark)|#[0-9a-fA-F]{3,6}\b|rgb\(|\.bounce\.|components\.filter\./', $html, $view);
            $this->assertStringNotContainsString('style="cursor', $html, $view);
        }
    }
}
