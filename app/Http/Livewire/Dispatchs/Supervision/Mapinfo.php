<?php

namespace App\Http\Livewire\Dispatchs\Supervision;

use App\Custom\WpaStatus;
use App\Models\{Service, Wpa};
use Livewire\{Component, WithPagination};

class Mapinfo extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $notas;
    public $user;

    protected $listeners = [
        'filterUser'
    ];



    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->with('Status')->first();

    }

    public function filterUser($user)
    {

        $this->user = $user;

        $wpas = Wpa::whereRelation('Production', 'service_id', $this->service->uuid)
            ->whereRelation('Production', 'user_id', $user)
            ->whereRelation('Production', 'confirmed', false)
            ->with('Production')
            ->get();

        $resultados = [];

        if ($wpas->count()) {

            foreach ($wpas as $wpa) {
                if ($wpa->lat < -14 && $wpa->long < -18 && $wpa->lat + $wpa->long != 0) {
                    $resultado = [
                        'coordenadas' => [(float) $wpa->lat, (float) $wpa->long],
                        'nota'        => $wpa->Note->note,
                        'dd'          => $wpa->dd,
                        'service'     => mb_strtoupper($wpa->Production->Service->service),
                        'group2'      => $wpa->Note ? $wpa->Note->group2 : '',
                        'material'    => $wpa->Note ? $wpa->Note->material : '',
                        'municipio'   => $wpa->Note ? $wpa->Note->lexp : '',
                        'equipe'      => $wpa->Production->User ? $wpa->Production->User->name : '',
                        'status'      => (WpaStatus::status($wpa->stats, $wpa->execstats))->info,
                        'color'       => (WpaStatus::status($wpa->stats, $wpa->execstats))->wpa_color,
                        'icon'        => (WpaStatus::status($wpa->stats, $wpa->execstats))->wpa_icon,
                        'nstat'       => $wpa->stats,
                        'estat'       => $wpa->execstats,
                    ];

                    $resultados[] = $resultado;
                }
            }

            $this->dispatchBrowserEvent('update_marks', ['wpa' => $resultados, 'clear' => true]);
        }

    }



    public function getWpasProperty()
    {
        return Wpa::wherehas('Production', function ($q) {
            return $q->where('service_id', $this->service->uuid)->where('confirmed', false);
        })->with('Production');
    }

    public function getListsProperty()
    {
        return Wpa::wherehas('Production', function ($q) {
            return $q->where('service_id', $this->service->uuid)->where('confirmed', false);
        })->with('Production');
    }

    public function teste()
    {
        $wpas = $this->wpas->get();

        $resultados = [];

        if ($wpas->count()) {

            foreach ($wpas as $wpa) {
                if ($wpa->lat < -14 && $wpa->long < -18 && $wpa->lat + $wpa->long != 0) {
                    $resultado = [
                        'coordenadas' => [(float) $wpa->lat, (float) $wpa->long],
                        'nota'        => $wpa->Note->note,
                        'dd'          => $wpa->dd,
                        'service'     => mb_strtoupper($wpa->Production->Service->service),
                        'group2'      => $wpa->Note ? $wpa->Note->group2 : '',
                        'material'    => $wpa->Note ? $wpa->Note->material : '',
                        'municipio'   => $wpa->Note ? $wpa->Note->lexp : '',
                        'equipe'      => $wpa->Production->User ? $wpa->Production->User->name : '',
                        'status'      => (WpaStatus::status($wpa->stats, $wpa->execstats))->info,
                        'color'       => (WpaStatus::status($wpa->stats, $wpa->execstats))->wpa_color,
                        'icon'        => (WpaStatus::status($wpa->stats, $wpa->execstats))->wpa_icon,
                        'nstat'       => $wpa->stats,
                        'estat'       => $wpa->execstats,
                    ];

                    $resultados[] = $resultado;
                }
            }

            $this->dispatchBrowserEvent('update_marks', ['wpa' => $resultados]);
        }
    }

    public function pegarCoordenadaNota($id)
    {
        $wpas = $this->wpas->where('id', $id)->get();

        $resultados = [];

        if ($wpas->count()) {

            foreach ($wpas as $wpa) {
                if ($wpa->lat < -14 && $wpa->long < -18 && $wpa->lat + $wpa->long != 0) {
                    $resultado = [
                        'coordenadas' => [(float) $wpa->lat, (float) $wpa->long],
                        'nota'        => $wpa->Note->note,
                        'dd'          => $wpa->dd,
                        'service'     => mb_strtoupper($wpa->Production->Service->service),
                        'group2'      => $wpa->Note ? $wpa->Note->group2 : '',
                        'material'    => $wpa->Note ? $wpa->Note->material : '',
                        'municipio'   => $wpa->Note ? $wpa->Note->lexp : '',
                        'equipe'      => $wpa->Production->User ? $wpa->Production->User->name : '',
                        'status'      => (WpaStatus::status($wpa->stats, $wpa->execstats))->info,
                        'color'       => (WpaStatus::status($wpa->stats, $wpa->execstats))->wpa_color,
                        'icon'        => (WpaStatus::status($wpa->stats, $wpa->execstats))->wpa_icon,
                        'nstat'       => $wpa->stats,
                        'estat'       => $wpa->execstats,
                    ];

                    $resultados[] = $resultado;
                }
            }

            $this->dispatchBrowserEvent('update_marks', ['wpa' => $resultados, 'clear' => true]);
        }
    }

    public function render()
    {
        return view('livewire.dispatchs.survey.mapinfo', [
            'lists' => $this->lists->paginate(11),
        ]);
    }
}
