<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Service;
use App\Models\SystemSetting;
use App\Models\Wall;
use App\Models\WallScreen;
use App\Models\WallScreenService;
use App\Services\Wall\WallDataOrchestrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WallController extends Controller
{
    public function index(WallDataOrchestrator $wallService)
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
            'productionSources' => [
                'rule_builder' => 'Rule Builder (Status)',
                'publication_note_filter' => 'Publication NoteFilter',
                'payment_note_filter' => 'Payment NoteFilter',
                'publish_repository' => 'Publish Repository',
                'supervision_repository' => 'Supervision Repository',
                'survey_repository' => 'Survey Repository',
            ],
            'productionFilterSchema' => $this->buildProductionFilterSchema(),
        ]);
    }

    public function storeWall(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'enabled' => 'nullable|boolean',
        ]);

        $nextOrder = ((int) Wall::query()->max('display_order')) + 1;

        Wall::query()->create([
            'name' => $data['name'],
            'enabled' => array_key_exists('enabled', $data) ? (bool) $data['enabled'] : true,
            'display_order' => $nextOrder,
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
            'enabled' => array_key_exists('enabled', $data) ? (bool) $data['enabled'] : $wall->enabled,
            'display_order' => array_key_exists('display_order', $data) ? (int) $data['display_order'] : $wall->display_order,
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

        SystemSetting::setValue(WallDataOrchestrator::KEY_ROTATION_SECONDS, (string) $data['rotation_seconds']);
        SystemSetting::setValue(WallDataOrchestrator::KEY_REFRESH_SECONDS, (string) $data['refresh_seconds']);

        return back()->with('success', 'Configurações globais do WALL atualizadas.');
    }

    public function storeScreen(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'wall_id' => 'required|integer|exists:walls,id',
            'name' => 'required|string|max:120',
            'screen_type' => 'required|string|in:production_services,fixed_chart,ads_chart',
            'fixed_chart' => 'nullable|string|in:ads_dashboard,complaints_dashboard,project_review_dashboard',
            'production_source' => 'nullable|string|in:rule_builder,publication_note_filter,payment_note_filter,publish_repository,supervision_repository,survey_repository',
            'production_sources_json' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'duration_seconds' => 'nullable|integer|min:10|max:3600',
            'service_rotation_seconds' => 'nullable|integer|min:10|max:3600',
        ]);

        if (($data['screen_type'] ?? '') === 'production_services') {
            $request->validate([
                'duration_seconds' => 'required|integer|min:10|max:3600',
                'service_rotation_seconds' => 'required|integer|min:10|max:3600',
            ]);
        } else {
            $request->validate([
                'duration_seconds' => 'required|integer|min:10|max:3600',
            ]);
        }

        $screenConfig = [];
        if (($data['screen_type'] ?? '') === 'ads_chart') {
            $screenConfig['fixed_chart'] = 'ads_dashboard';
        }
        if (($data['screen_type'] ?? '') === 'fixed_chart') {
            $screenConfig['fixed_chart'] = $data['fixed_chart'] ?? 'ads_dashboard';
        }
        if (($data['screen_type'] ?? '') === 'production_services') {
            $screenConfig['production_source'] = (string) ($data['production_source'] ?? 'rule_builder');
            $screenConfig['production_sources'] = $this->parseProductionSourcesConfig((string) ($data['production_sources_json'] ?? ''));
        }

        $nextOrder = ((int) WallScreen::query()
            ->where('wall_id', (int) $data['wall_id'])
            ->max('display_order')) + 1;

        WallScreen::query()->create([
            'wall_id' => (int) $data['wall_id'],
            'name' => $data['name'],
            'screen_type' => $data['screen_type'],
            'enabled' => array_key_exists('enabled', $data) ? (bool) $data['enabled'] : true,
            'display_order' => $nextOrder,
            'duration_seconds' => (int) ($data['duration_seconds'] ?? 600),
            'service_rotation_seconds' => ($data['screen_type'] ?? '') === 'production_services'
                ? (int) ($data['service_rotation_seconds'] ?? 180)
                : null,
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
            'production_source' => 'nullable|string|in:rule_builder,publication_note_filter,payment_note_filter,publish_repository,supervision_repository,survey_repository',
            'production_sources_json' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:1000',
            'duration_seconds' => 'nullable|integer|min:10|max:3600',
            'service_rotation_seconds' => 'nullable|integer|min:10|max:3600',
        ]);

        if (($data['screen_type'] ?? '') === 'production_services') {
            $request->validate([
                'duration_seconds' => 'required|integer|min:10|max:3600',
                'service_rotation_seconds' => 'required|integer|min:10|max:3600',
            ]);
        } else {
            $request->validate([
                'duration_seconds' => 'required|integer|min:10|max:3600',
            ]);
        }

        $screenConfig = [];
        if (($data['screen_type'] ?? '') === 'ads_chart') {
            $screenConfig['fixed_chart'] = 'ads_dashboard';
        }
        if (($data['screen_type'] ?? '') === 'fixed_chart') {
            $screenConfig['fixed_chart'] = $data['fixed_chart'] ?? 'ads_dashboard';
        }
        if (($data['screen_type'] ?? '') === 'production_services') {
            $screenConfig['production_source'] = (string) ($data['production_source'] ?? 'rule_builder');
            $screenConfig['production_sources'] = $this->parseProductionSourcesConfig((string) ($data['production_sources_json'] ?? ''));
        }

        $screen->update([
            'wall_id' => (int) $data['wall_id'],
            'name' => $data['name'],
            'screen_type' => $data['screen_type'],
            'enabled' => array_key_exists('enabled', $data) ? (bool) $data['enabled'] : $screen->enabled,
            'display_order' => array_key_exists('display_order', $data) ? (int) $data['display_order'] : $screen->display_order,
            'duration_seconds' => (int) ($data['duration_seconds'] ?? 600),
            'service_rotation_seconds' => ($data['screen_type'] ?? '') === 'production_services'
                ? (int) ($data['service_rotation_seconds'] ?? 180)
                : null,
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
        ]);

        $nextOrder = ((int) WallScreenService::query()
            ->where('wall_screen_id', $screen->id)
            ->max('display_order')) + 1;

        WallScreenService::query()->create([
            'wall_screen_id' => $screen->id,
            'service_id' => $data['service_id'],
            'previous_service_id' => $data['previous_service_id'] ?? null,
            'enabled' => array_key_exists('enabled', $data) ? (bool) $data['enabled'] : true,
            'use_rule_builder' => array_key_exists('use_rule_builder', $data) ? (bool) $data['use_rule_builder'] : true,
            'display_order' => $nextOrder,
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
            'enabled' => array_key_exists('enabled', $data) ? (bool) $data['enabled'] : $item->enabled,
            'use_rule_builder' => array_key_exists('use_rule_builder', $data) ? (bool) $data['use_rule_builder'] : $item->use_rule_builder,
            'display_order' => array_key_exists('display_order', $data) ? (int) $data['display_order'] : $item->display_order,
        ]);

        return back()->with('success', 'Serviço da tela atualizado.');
    }

    public function destroyItem(WallScreenService $item): RedirectResponse
    {
        $item->delete();

        return back()->with('success', 'Serviço da tela removido.');
    }

    private function parseProductionSourcesConfig(string $json): array
    {
        $trimmed = trim($json);
        if ($trimmed === '') {
            return [];
        }

        try {
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw ValidationException::withMessages([
                'production_sources_json' => 'JSON inválido em Fontes por serviço.',
            ]);
        }

        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'production_sources_json' => 'Fontes por serviço deve ser um objeto JSON.',
            ]);
        }

        $allowedSources = [
            'rule_builder',
            'publication_note_filter',
            'payment_note_filter',
            'publish_repository',
            'supervision_repository',
            'survey_repository',
        ];

        $normalized = [];
        foreach ($decoded as $serviceId => $rawConfig) {
            $serviceId = (string) $serviceId;
            if ($serviceId === '') {
                continue;
            }

            if (is_string($rawConfig)) {
                $source = trim($rawConfig);
                if (!in_array($source, $allowedSources, true)) {
                    throw ValidationException::withMessages([
                        'production_sources_json' => "Fonte inválida para {$serviceId}: {$source}",
                    ]);
                }
                $normalized[$serviceId] = ['source' => $source];
                continue;
            }

            if (!is_array($rawConfig)) {
                throw ValidationException::withMessages([
                    'production_sources_json' => "Config inválida para {$serviceId}.",
                ]);
            }

            $source = trim((string) ($rawConfig['source'] ?? ''));
            if ($source === '' || !in_array($source, $allowedSources, true)) {
                throw ValidationException::withMessages([
                    'production_sources_json' => "Fonte inválida para {$serviceId}.",
                ]);
            }

            $config = $rawConfig;
            $config['source'] = $source;
            $config['query_filters'] = $this->normalizeQueryFilters($config['query_filters'] ?? []);
            $normalized[$serviceId] = $config;
        }

        return $normalized;
    }

    private function normalizeQueryFilters(mixed $filters): array
    {
        if (!is_array($filters)) {
            return [];
        }

        $allowedModes = ['include', 'exclude'];
        $allowedScopes = ['base', 'relation'];
        $allowedOperators = ['equals', 'starts_with', 'contains', 'ends_with'];

        $normalized = [];
        foreach ($filters as $index => $raw) {
            if (!is_array($raw)) {
                continue;
            }

            $mode = trim((string) ($raw['mode'] ?? 'include'));
            $scope = trim((string) ($raw['scope'] ?? 'base'));
            $column = trim((string) ($raw['column'] ?? ''));
            $operator = trim((string) ($raw['operator'] ?? 'equals'));
            $relation = trim((string) ($raw['relation'] ?? ''));
            $value = array_key_exists('value', $raw) ? (string) ($raw['value'] ?? '') : '';

            if (!in_array($mode, $allowedModes, true)) {
                throw ValidationException::withMessages([
                    'production_sources_json' => "Filtro #{$index}: mode inválido.",
                ]);
            }

            if (!in_array($scope, $allowedScopes, true)) {
                throw ValidationException::withMessages([
                    'production_sources_json' => "Filtro #{$index}: scope inválido.",
                ]);
            }

            if (!in_array($operator, $allowedOperators, true)) {
                throw ValidationException::withMessages([
                    'production_sources_json' => "Filtro #{$index}: operator inválido.",
                ]);
            }

            if ($column === '') {
                throw ValidationException::withMessages([
                    'production_sources_json' => "Filtro #{$index}: coluna é obrigatória.",
                ]);
            }

            if ($scope === 'relation' && $relation === '') {
                throw ValidationException::withMessages([
                    'production_sources_json' => "Filtro #{$index}: relation é obrigatória no escopo relation.",
                ]);
            }

            $normalized[] = [
                'mode' => $mode,
                'scope' => $scope,
                'relation' => $scope === 'relation' ? $relation : '',
                'column' => $column,
                'operator' => $operator,
                'value' => $value,
            ];
        }

        return $normalized;
    }

    private function buildProductionFilterSchema(): array
    {
        $note = new Note();
        $relations = ['Orders', 'WorkForm', 'Partials', 'FiveNote', 'Productions', 'RamalForm'];

        $relationMap = [];
        foreach ($relations as $relationName) {
            try {
                $relation = $note->{$relationName}();
                $related = $relation->getRelated();
                $relationMap[$relationName] = [
                    'model' => $related::class,
                    'columns' => method_exists($related, 'getFillable')
                        ? array_values($related->getFillable())
                        : [],
                ];
            } catch (\Throwable $e) {
                $relationMap[$relationName] = [
                    'model' => null,
                    'columns' => [],
                ];
            }
        }

        return [
            'base' => [
                'model' => Note::class,
                'columns' => array_values($note->getFillable()),
            ],
            'relations' => $relationMap,
        ];
    }
}
