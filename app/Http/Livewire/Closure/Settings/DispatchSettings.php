<?php

namespace App\Http\Livewire\Closure\Settings;

use App\Services\Closure\{ClosureMetaClassifier, ClosureSettings};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Livewire\Component;

/**
 * Configurações do Encerramento (Gestão do ciclo): auto-atribuição pelo usuário, finalização de meta futura e
 * rubricas fora do Encerramento. Antes viviam numa gaveta da Lista do Despacho.
 */
class DispatchSettings extends Component
{
    public bool $allowFutureCompletion = false;

    public bool $selfAssignEnabled = false;

    /** @var array<int, string> */
    public array $selfAssignTypes = [];

    /** @var array<int, string> */
    public array $excludedRubrics = [];

    public function mount(): void
    {
        $this->authorizeDispatch();
        $this->load();
    }

    public function render()
    {
        $known = ClosureDispatchListQuery::rubricOptions();

        return view('livewire.closure.settings.dispatch-settings', [
            // Rubricas salvas que sumiram do cadastro continuam aparecendo, para dar para desmarcar.
            'rubrics' => array_values(array_unique(array_merge($known, $this->excludedRubrics))),
            'types'   => ClosureMetaClassifier::TYPES,
        ]);
    }

    public function save(): void
    {
        $this->authorizeDispatch();

        $settings = app(ClosureSettings::class);
        $actor    = auth()->user();

        $settings->setAllowFutureCompletion($this->allowFutureCompletion, $actor);
        $settings->setSelfAssignEnabled($this->selfAssignEnabled, $actor);
        $settings->setSelfAssignTypes($this->selfAssignTypes, $actor);
        $settings->setExcludedRubrics($this->excludedRubrics, $actor);

        $this->load();
        $this->dispatchBrowserEvent('notification:toast', [
            'icon'  => 'success',
            'title' => 'Configurações salvas',
            'text'  => 'As configurações do Encerramento foram atualizadas.',
        ]);
    }

    public function restoreDefaultRubrics(): void
    {
        $this->excludedRubrics = ClosureSettings::DEFAULT_EXCLUDED_RUBRICS;
    }

    private function load(): void
    {
        $settings = app(ClosureSettings::class);

        $this->allowFutureCompletion = $settings->allowFutureCompletion();
        $this->selfAssignEnabled     = $settings->selfAssignEnabled();
        $this->selfAssignTypes       = $settings->selfAssignTypes();
        $this->excludedRubrics       = $settings->excludedRubrics();
    }

    private function authorizeDispatch(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }
}
