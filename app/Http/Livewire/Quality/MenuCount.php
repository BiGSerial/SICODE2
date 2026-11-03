<?php

namespace App\Http\Livewire\Quality;

use App\Services\Quality\QualityBoard;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/**
 * Pílula de contagem de um item do menu lateral da Qualidade (declarada em `QualityNav`, `counts`). `scope` = `n1`, `n2` (abas, com a
 * obra mais antiga) ou `mgmt` (contagens da Gestão); `tab` = a aba ou a contagem. Vermelha quando há obra parada há 5+ dias, âmbar quando
 * depende de você, cinza quando só acompanha. Os números são calculados uma vez por usuário a cada 10 s (vários itens leem o mesmo
 * conjunto) e atualizam sozinhos a cada 15 s.
 */
class MenuCount extends Component
{
    public const LATE_DAYS = 5;

    public string $scope = 'n1';

    public string $tab = '';

    public function mount(string $scope, string $tab): void
    {
        $this->scope = $scope;
        $this->tab   = $tab;
    }

    public function render(QualityBoard $board)
    {
        $user = auth()->user();
        $data = $user ? Cache::remember("quality-menu:{$user->id}:{$this->scope}", 10, fn () => match ($this->scope) {
            'n1'    => $board->tabs($user, QualityBoard::N1_TABS),
            'n2'    => $board->tabs($user, QualityBoard::N2_TABS),
            default => $board->managementCounts($user),
        }) : [];

        return view('livewire.quality.menu-count', ['pill' => $this->pill($data)]);
    }

    /** @return array{count: int, tone: string, title: string}|null */
    private function pill(array $data): ?array
    {
        if ($this->scope === 'mgmt') {
            $count = (int) ($data[$this->tab] ?? 0);

            return match (true) {
                $this->tab === 'paradas' => $count > 0 ? ['count' => $count, 'tone' => 'danger', 'title' => 'Obras paradas há ' . self::LATE_DAYS . '+ dias'] : null,
                default                  => ['count' => $count, 'tone' => 'secondary', 'title' => 'Em acompanhamento'],
            };
        }

        $tab = $data[$this->tab] ?? null;

        if (!$tab || $tab['count'] < 1) {
            return null;
        }

        $late = $tab['oldest'] && $tab['oldest']->lt(now()->subDays(self::LATE_DAYS));

        return [
            'count' => (int) $tab['count'],
            'tone'  => $late ? 'danger' : ($tab['action'] ? 'warning' : 'secondary'),
            'title' => $late ? 'Há obra parada há ' . self::LATE_DAYS . '+ dias' : ($tab['action'] ? 'Depende de você' : 'Em acompanhamento'),
        ];
    }
}
