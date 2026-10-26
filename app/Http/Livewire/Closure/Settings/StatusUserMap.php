<?php

namespace App\Http\Livewire\Closure\Settings;

use App\Services\Closure\{ClosureArea, ClosureStatusUserRouteService};
use InvalidArgumentException;
use Livewire\Component;

/**
 * Mapa "status usuário → serviço" (gestão do Encerramento). Cada serviço com caixa (Medição, Fiscalização) escolhe os status
 * que pertencem a ele; um status só pode estar em UM serviço. Alimenta a aba "Em acompanhamento" das caixas.
 */
class StatusUserMap extends Component
{
    /** @var array<string, array<int, string>> área => prefixos marcados (ainda não salvos) */
    public array $selected = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.manage'), 403);
        $this->reload();
    }

    public function render()
    {
        return view('livewire.closure.settings.status-user-map', [
            'areas'   => ClosureArea::inboxOptions(),
            'catalog' => app(ClosureStatusUserRouteService::class)->catalog(),
        ]);
    }

    public function save(string $area): void
    {
        abort_unless(auth()->user()?->can('closure.manage'), 403);
        abort_unless(ClosureArea::hasInbox($area), 404);

        try {
            $result = app(ClosureStatusUserRouteService::class)->sync($area, $this->selected[$area] ?? [], auth()->user());
        } catch (InvalidArgumentException $exception) {
            $this->toast('Não foi possível salvar', $exception->getMessage(), 'error');
            $this->reload();

            return;
        }

        $this->reload();
        $this->toast('Mapa salvo', ClosureArea::label($area) . ": {$result['added']} status adicionado(s), {$result['removed']} removido(s).");
    }

    /** Volta a seleção ao que está salvo (descarta marcações não salvas). */
    public function reload(): void
    {
        $service = app(ClosureStatusUserRouteService::class);

        foreach (ClosureArea::inboxAreas() as $area) {
            $this->selected[$area] = $service->prefixesFor($area);
        }
    }

    private function toast(string $title, string $text, string $icon = 'success'): void
    {
        $this->dispatchBrowserEvent('notification:toast', ['title' => $title, 'text' => $text, 'icon' => $icon]);
    }
}
