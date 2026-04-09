<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\SystemSetting;
use App\Models\Wall;
use App\Models\WallScreen;
use App\Models\WallScreenService;
use App\Services\Reports\ProductionWallV2DataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WallController extends Controller
{
    public function index(ProductionWallV2DataService $wallService)
    {
        $walls = Wall::query()
            ->with(['screens' => function ($q) {
                $q->with(['items' => function ($sq) {
                    $sq->with(['service', 'previousService'])
                        ->orderBy('display_order')
                        ->orderBy('id');
                }])->orderBy('display_order')->orderBy('id');
            }])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $services = Service::query()->orderBy('service')->get(['uuid', 'service']);

        return view('config.wall.index', [
            'walls' => $walls,
            'services' => $services,
            'rotationSeconds' => $wallService->rotationSeconds(),
            'refreshSeconds' => $wallService->refreshSeconds(),
            'screenTypes' => [
                'production_services' => 'Produção',
                'fixed_chart' => 'FIXO',
            ],
            'fixedCharts' => [
                'ads_dashboard' => 'ADS',
                'complaints_dashboard' => 'RECLAMAÇÃO',
                'project_review_dashboard' => 'ANALISE DE PROJETO',
            ],
        ]);
    }

    public function storeWall(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'enabled' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:1000',
        ]);

        Wall::query()->create([
            'name' => $data['name'],
            'enabled' => (bool) ($data['enabled'] ?? false),
            'display_order' => (int) ($data['display_order'] ?? 0),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Wall criado com sucesso.');
    }

    public function updateWall(Request $request, Wall $wall): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'enabled' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:1000',
        ]);

        $wall->update([
            'name' => $data['name'],
            'enabled' => (bool) ($data['enabled'] ?? false),
            'display_order' => (int) ($data['display_order'] ?? 0),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Wall atualizado.');
    }

    public function destroyWall(Wall $wall): RedirectResponse
    {
        $wall->delete();

        return back()->with('success', 'Wall removido.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rotation_seconds' => 'required|integer|min:10|max:3600',
            'refresh_seconds' => 'required|integer|min:10|max:3600',
        ]);

        SystemSetting::setValue(ProductionWallV2DataService::KEY_ROTATION_SECONDS, (string) $data['rotation_seconds']);
        SystemSetting::setValue(ProductionWallV2DataService::KEY_REFRESH_SECONDS, (string) $data['refresh_seconds']);

        return back()->with('success', 'Configurações globais do WALL atualizadas.');
    }

    public function storeScreen(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'wall_id' => 'required|integer|exists:walls,id',
            'name' => 'required|string|max:120',
            'screen_type' => 'required|string|in:production_services,fixed_chart,ads_chart',
            'fixed_chart' => 'nullable|string|in:ads_dashboard,complaints_dashboard,project_review_dashboard',
            'enabled' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:1000',
            'duration_seconds' => 'nullable|integer|min:10|max:3600',
            'service_rotation_seconds' => 'nullable|integer|min:10|max:3600',
        ]);

        $screenConfig = [];
        if (($data['screen_type'] ?? '') === 'ads_chart') {
            $screenConfig['fixed_chart'] = 'ads_dashboard';
        }
        if (($data['screen_type'] ?? '') === 'fixed_chart') {
            $screenConfig['fixed_chart'] = $data['fixed_chart'] ?? 'ads_dashboard';
        }

        WallScreen::query()->create([
            'wall_id' => (int) $data['wall_id'],
            'name' => $data['name'],
            'screen_type' => $data['screen_type'],
            'enabled' => (bool) ($data['enabled'] ?? false),
            'display_order' => (int) ($data['display_order'] ?? 0),
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'service_rotation_seconds' => $data['service_rotation_seconds'] ?? null,
            'screen_config' => $screenConfig ?: null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Tela do WALL criada.');
    }

    public function updateScreen(Request $request, WallScreen $screen): RedirectResponse
    {
        $data = $request->validate([
            'wall_id' => 'required|integer|exists:walls,id',
            'name' => 'required|string|max:120',
            'screen_type' => 'required|string|in:production_services,fixed_chart,ads_chart',
            'fixed_chart' => 'nullable|string|in:ads_dashboard,complaints_dashboard,project_review_dashboard',
            'enabled' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:1000',
            'duration_seconds' => 'nullable|integer|min:10|max:3600',
            'service_rotation_seconds' => 'nullable|integer|min:10|max:3600',
        ]);

        $screenConfig = [];
        if (($data['screen_type'] ?? '') === 'ads_chart') {
            $screenConfig['fixed_chart'] = 'ads_dashboard';
        }
        if (($data['screen_type'] ?? '') === 'fixed_chart') {
            $screenConfig['fixed_chart'] = $data['fixed_chart'] ?? 'ads_dashboard';
        }

        $screen->update([
            'wall_id' => (int) $data['wall_id'],
            'name' => $data['name'],
            'screen_type' => $data['screen_type'],
            'enabled' => (bool) ($data['enabled'] ?? false),
            'display_order' => (int) ($data['display_order'] ?? 0),
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'service_rotation_seconds' => $data['service_rotation_seconds'] ?? null,
            'screen_config' => $screenConfig ?: null,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Tela do WALL atualizada.');
    }

    public function destroyScreen(WallScreen $screen): RedirectResponse
    {
        $screen->delete();

        return back()->with('success', 'Tela do WALL removida.');
    }

    public function storeItem(Request $request, WallScreen $screen): RedirectResponse
    {
        $data = $request->validate([
            'service_id' => 'required|string|exists:services,uuid',
            'previous_service_id' => 'nullable|string|different:service_id|exists:services,uuid',
            'enabled' => 'nullable|boolean',
            'use_rule_builder' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:1000',
        ]);

        WallScreenService::query()->create([
            'wall_screen_id' => $screen->id,
            'service_id' => $data['service_id'],
            'previous_service_id' => $data['previous_service_id'] ?? null,
            'enabled' => (bool) ($data['enabled'] ?? false),
            'use_rule_builder' => (bool) ($data['use_rule_builder'] ?? false),
            'display_order' => (int) ($data['display_order'] ?? 0),
        ]);

        return back()->with('success', 'Serviço da tela adicionado.');
    }

    public function updateItem(Request $request, WallScreenService $item): RedirectResponse
    {
        $data = $request->validate([
            'service_id' => 'required|string|exists:services,uuid',
            'previous_service_id' => 'nullable|string|different:service_id|exists:services,uuid',
            'enabled' => 'nullable|boolean',
            'use_rule_builder' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:1000',
        ]);

        $item->update([
            'service_id' => $data['service_id'],
            'previous_service_id' => $data['previous_service_id'] ?? null,
            'enabled' => (bool) ($data['enabled'] ?? false),
            'use_rule_builder' => (bool) ($data['use_rule_builder'] ?? false),
            'display_order' => (int) ($data['display_order'] ?? 0),
        ]);

        return back()->with('success', 'Serviço da tela atualizado.');
    }

    public function destroyItem(WallScreenService $item): RedirectResponse
    {
        $item->delete();

        return back()->with('success', 'Serviço da tela removido.');
    }
}
