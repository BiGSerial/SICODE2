<?php

namespace App\Http\Livewire\Dispatchs\Survey;

use App\Custom\WpaStatus;
use App\Models\{Service, Production};
use Livewire\{Component, WithPagination};

class Mapinfo extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $notas;
    public $user;
    public $search;

    protected $listeners = [
        'filterUser'
    ];



    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->with('Status')->first();

    }

    public function filterUser($user)
    {
        $this->gotoPage(1);

        $this->user = $user;

        $wpas = $this->lists->get();

        $resultados = [];

        if ($wpas->count()) {

            foreach ($wpas as $production) {

                if ($wpa = $production->Wpas->isNotEmpty() ? $production->Wpas->last() : false) {
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
            }

            $this->dispatchBrowserEvent('update_marks', ['wpa' => $resultados, 'clear' => true]);
        }

    }

    public function toSearch()
    {
        $this->gotoPage(1);

        $wpas = $this->lists->get();

        $resultados = [];

        if ($wpas->count()) {

            foreach ($wpas as $production) {

                if ($wpa = $production->Wpas->isNotEmpty() ? $production->Wpas->last() : false) {
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
            }

            $this->dispatchBrowserEvent('update_marks', ['wpa' => $resultados, 'clear' => true]);
        }
    }

    // public function getWpasProperty()
    // {
    //     return Wpa::wherehas('Production', function ($q) {
    //         return $q->where('service_id', $this->service->uuid)->where('confirmed', false);
    //     })->with('Production');
    // }

    public function getListsProperty()
    {
        // return Wpa::when($this->user, function ($q) {
        //     $q->whereRelation('Production', 'user_id', $this->user);
        // })
        // ->when($this->search, function ($q) {
        //     $q->where(function ($sq) {
        //         $sq->whereRelation('Note', 'note', 'like', "%".trim($this->search)."%")
        //         ->orWhereRelation('Note.Orders', 'ordem', 'like', "%".trim($this->search)."%")
        //         ->orWhere('dd', 'like', "%".trim($this->search)."%");
        //     });
        // })
        // ->whereRelation('Production', 'service_id', $this->service->uuid)
        // ->whereRelation('Production', 'confirmed', false)
        // ->with('Production');

        return Production::when($this->user, function ($q) {
            $q->where('user_id', $this->user);
        })->when($this->search, function ($q) {
            $q->whereRelation('Note', function ($sq) {
                $sq->where('note', 'like', "%".trim($this->search)."%")
                ->orWhereRelation('Orders', 'ordem', 'like', "%".trim($this->search)."%")
                ->orWhereRelation('Wpas', 'dd', 'like', "%".trim($this->search)."%");
            });
        })->where('service_id', $this->service->uuid)
            ->where('confirmed', false)
            ->orderBy('dispatch_at', 'asc');
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

    public function pegarCoordenadaNota(Production $production)
    {


        $resultados = [];

        if ($production) {

            if ($wpa = $production->Wpas->isNotEmpty() ? $production->Wpas->last() : false) {
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
            'lists' => $this->lists->paginate(30),
        ]);
    }
}
