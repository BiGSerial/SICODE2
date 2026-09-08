<?php

namespace App\Http\Livewire\Config\Services;

use App\Models\{AuxiliarService, Note, Service};
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Addstatus extends Component
{
    public $showAddstatus = false;

    public $service;

    public $status_l;

    public $status_s;

    public $status_list;

    public $exclusion;

    public $value;

    public $condition;

    public $column_search;

    public $exclusion2;

    public $value2;

    public $condition2;

    public $column_search2;

    public $view_and = false;

    public $columns_l;

    protected $listeners = [
        'open_add_status'       => 'open_add_status',
        'confirm_remove_status' => 'remove',
    ];

    public const CONDITIONS = [
        'Exatamente'  => 'É exatamente',
        'Diferente'   => 'É diferente de',
        'Inicia por'  => 'Inicia por',
        'Termina por' => 'Termina por',
        'Contem'      => 'Contém',
        'Em'          => 'Está em (lista)',
        'NaoEstaEm'   => 'Não está em (lista)',
    ];

    public const LIST_CONDITIONS = ['Em', 'NaoEstaEm'];

    public function prettyColumn(?string $column): string
    {
        if (!$column) {
            return '';
        }

        $spaced = preg_replace('/(?<!^)(?=[A-Z])/', ' ', $column);
        $spaced = str_replace('_', ' ', $spaced);

        return ucwords(trim($spaced));
    }

    public function conditionLabel(?string $condition): string
    {
        return self::CONDITIONS[$condition] ?? (string) $condition;
    }

    public function conditionIsList(?string $condition): bool
    {
        return in_array($condition, self::LIST_CONDITIONS, true);
    }

    /**
     * Mostra o valor gravado de forma legível — decodifica a lista
     * (JSON ou CSV antigo) em "2, 4, 7" em vez do JSON crú.
     */
    public function displayValue(?string $value, ?string $condition): string
    {
        if (!$this->conditionIsList($condition) || !$value) {
            return (string) $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? implode(', ', $decoded) : $value;
    }

    /**
     * Converte o texto digitado ("2, 4, 7") em JSON de array para
     * gravar. Fora das condições de lista, devolve o valor como veio.
     */
    protected function encodeValueForStorage(?string $rawValue, ?string $condition): ?string
    {
        if (!$this->conditionIsList($condition)) {
            return $rawValue;
        }

        $items = array_values(array_filter(array_map('trim', explode(',', (string) $rawValue)), fn ($v) => $v !== ''));

        return $items ? json_encode($items) : null;
    }

    public function and()
    {
        if ($this->view_and) {
            $this->view_and = false;

            $this->exclusion2     = false;
            $this->value2         = '';
            $this->condition2     = '';
            $this->column_search2 = '';
        } else {
            $this->view_and = true;
        }
    }

    public function mount()
    {
        $this->columns_l = (new Note())->getFillable();
    }

    public function open_add_status(Service $service)
    {
        $this->service = $service->load('Status');

        // dd($this->service);

        $this->showAddstatus = true;

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'add_status_modal',
        ]);
    }

    public function add()
    {
        if (!$this->column_search || !trim((string) $this->column_search) || $this->column_search === 'Selecione o Campo') {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'warning',
                'menssage' => 'Selecione a coluna de busca antes de adicionar o filtro.',
            ]);

            return;
        }

        if (!$this->condition || $this->condition === 'Selecione') {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'warning',
                'menssage' => 'Selecione a condição antes de adicionar o filtro.',
            ]);

            return;
        }

        if ($this->value === null || trim((string) $this->value) === '') {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'warning',
                'menssage' => 'Informe o valor a ser filtrado.',
            ]);

            return;
        }

        $valueToStore = $this->encodeValueForStorage($this->value, $this->condition);

        if ($this->conditionIsList($this->condition) && !$valueToStore) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'warning',
                'menssage' => 'Informe ao menos um valor válido, separado por vírgula.',
            ]);

            return;
        }

        $value2ToStore = $this->encodeValueForStorage($this->value2, $this->condition2);

        $aux = AuxiliarService::Where('service_id', $this->service->uuid)->where('value', $valueToStore)->first();

        if (!$aux || ($aux && $value2ToStore != $aux->value2)) {
            AuxiliarService::create([
                'service_id'     => $this->service->uuid,
                'column_search'  => trim($this->column_search),
                'condition'      => $this->condition,
                'exclusion'      => $this->exclusion ? true : false,
                'value'          => $valueToStore,
                'column_search2' => trim((string) $this->column_search2),
                'condition2'     => $this->condition2,
                'exclusion2'     => $this->exclusion2 ? true : false,
                'value2'         => $value2ToStore,
            ]);

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Filtro adicionado com sucesso!',
            ]);

            $this->reset_form();

            return;
        }

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'warning',
            'menssage' => 'Já existe um filtro igual a este para o serviço.',
        ]);
    }

    public function reset_form()
    {
        $this->column_search  = '';
        $this->condition      = '';
        $this->value          = '';
        $this->exclusion      = false;
        $this->column_search2 = '';
        $this->condition2     = '';
        $this->value2         = '';
        $this->exclusion2     = false;
        $this->view_and       = false;
    }

    public function to_remove($id)
    {
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover Filtro',
            'msg'           => 'Deseja realmente remover este filtro? Essa ação não pode ser desfeita.',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, remover!',
            'btnCanceltxt'  => 'Cancelar',
            'action'        => 'confirm_remove_status',
            'action_id'     => $id,
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum filtro foi removido.',
        ]);
    }

    public function remove($id)
    {
        AuxiliarService::find($id)?->delete();

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Filtro removido com sucesso!',
        ]);
    }

    public function render()
    {
        // $this->status_l = Note::select('nstats', DB::raw('MAX(status) as status'))
        // ->orderBy('nstats')
        // ->groupBy('nstats')
        // ->get();

        if ($this->service) {
            $this->status_list = AuxiliarService::where('service_id', $this->service->uuid)->orderBy('exclusion')->orderBy('value')->get();
        }

        return view('livewire.config.services.addstatus');
    }
}
