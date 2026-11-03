<?php

namespace App\Http\Livewire\Services\Concerns;

use App\Helpers\SelectOptions;
use App\Models\{Notetimeline, Production};
use App\Support\SicodeRules;
use Illuminate\Support\Facades\DB;

/**
 * Encerramento em massa do ACOMPANHAMENTO de Serviços. Uma lógica só para as três variantes (`ActivityAccompanyProfile`, chave `bulk`):
 * `analysis` e `preanalysis` (conclusão validada pelas regras do SICODE) e `reverse` (tipo de estudo + conclusão que combina com ele).
 * Atividade sem `bulk` no perfil não encerra em massa. Só encerra o que é do usuário (ou do usuário que o superadm está vendo).
 */
trait ClosesAccompanyInBulk
{
    public $bulkPreResult;

    public $bulkConclusion;

    public $bulkMmgd;

    public $bulkIs45;

    public $bulkInfo;

    protected function bulkMode(): ?string
    {
        return $this->profile()['bulk'] ?? null;
    }

    public function updatedBulkPreResult(): void
    {
        $this->bulkConclusion = null;
    }

    private function bulkWarning(string $title, string $html): void
    {
        $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'warning', 'title' => $title, 'html' => $html]);
    }

    /** Conclusão válida para a variante da atividade. */
    private function bulkConclusionIsValid(): bool
    {
        return match ($this->bulkMode()) {
            'preanalysis' => SicodeRules::isValidPreAnalysisConclusion($this->bulkConclusion),
            'reverse'     => SelectOptions::isValidReverseFluxConclusion($this->bulkPreResult, $this->bulkConclusion),
            default       => SicodeRules::isValidAnalysisConclusion($this->bulkConclusion),
        };
    }

    public function confirmBulkClose(): void
    {
        if (!$this->bulkMode()) {
            return;
        }

        if (!count($this->selected)) {
            $this->bulkWarning('SELECIONE REGISTROS', 'Selecione ao menos uma nota para encerrar em massa.');

            return;
        }

        if ($this->bulkMode() === 'reverse' && !$this->bulkPreResult) {
            $this->bulkWarning('TIPO DE ESTUDO NÃO DEFINIDO', 'Informe o tipo de estudo que será aplicado aos registros selecionados.');

            return;
        }

        if (!$this->bulkConclusion) {
            $this->bulkWarning('CONCLUSÃO NÃO DEFINIDA', 'Informe a conclusão que será aplicada aos registros selecionados.');

            return;
        }

        if (!$this->bulkConclusionIsValid()) {
            $this->bulkWarning('CONCLUSÃO INVÁLIDA', $this->bulkMode() === 'reverse'
                ? 'A conclusão selecionada não corresponde ao tipo de estudo informado.'
                : 'A conclusão selecionada não está liberada para esta configuração do SICODE.');

            return;
        }

        if (!$this->bulkMmgd) {
            $this->bulkWarning('INFORMAÇÃO OBRIGATÓRIA', 'Obrigatório informar MMGD para encerrar em massa.');

            return;
        }

        $count = count($this->selected);

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'ENCERRAMENTO EM MASSA',
            'msg'   => "Você está prestes encerrar <strong>{$count}</strong> registro(s).<br>
                Ao encerrar, entendemos que você seguiu todos os procedimentos em relação as transações no SAP.
                Uma vez encerrado, essa operação nao poderá ser desfeita.
                <h4 class='text-center mt-3'>DESEJA CONTINUAR?</h4>",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Continue!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_finish_mass',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Ação Cancelada.',
        ]);
    }

    public function finishBulk(): void
    {
        if (!$this->bulkMode()) {
            return;
        }

        if (!$this->bulkConclusionIsValid()) {
            $this->bulkWarning('CONCLUSÃO INVÁLIDA', 'A conclusão selecionada não está liberada para esta configuração do SICODE.');

            return;
        }

        $mmgd = $this->bulkMmgd === 'SIM';

        $productions = Production::query()
            ->whereIn('id', $this->selected)
            ->where('service_id', $this->service->uuid)
            ->where('completed', false)
            ->where('user_id', $this->targetUserId())
            ->with('Note', 'Analise')
            ->get();

        if (!$productions->count()) {
            $this->bulkWarning('NENHUM REGISTRO', 'Nenhuma nota válida foi encontrada para encerrar.');

            return;
        }

        DB::beginTransaction();

        try {
            $user          = Auth()->user()->name;
            $bulkSignature = 'encerrado em massa - de um total de ' . $productions->count() . ' registros';
            $info          = trim((string) $this->bulkInfo);
            $infoMessage   = $info !== '' ? "{$info} | {$bulkSignature}" : $bulkSignature;

            foreach ($productions as $production) {
                $analise = $production->Analise()->first() ?? $production->Analise()->create();

                $analise->update(array_filter([
                    'preresult'  => $this->bulkMode() === 'reverse' ? $this->bulkPreResult : null,
                    'conclusion' => $this->bulkConclusion,
                    'info'       => $infoMessage,
                ], fn ($value, $key) => $key !== 'preresult' || $value !== null, ARRAY_FILTER_USE_BOTH));

                $production->update([
                    'status'       => 5,
                    'completed_at' => date('Y-m-d H:i:s'),
                    'completed'    => true,
                    'confirmed'    => false,
                    'mmgd'         => $mmgd,
                ]);

                $production->Note?->update(['mmgd' => $mmgd, 'is45' => $this->bulkIs45]);

                Notetimeline::create([
                    'note_id'       => $production->note_id,
                    'service_id'    => $production->service_id,
                    'user_id'       => Auth()->user()->id,
                    'info'          => "Usuário {$user} encerrou a Nota/OV.",
                    'status'        => 5,
                    'production_id' => $production->id,
                ]);
            }

            DB::commit();

            $this->selected      = [];
            $this->bulkPreResult = $this->bulkConclusion = $this->bulkMmgd = $this->bulkIs45 = $this->bulkInfo = null;

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Encerramento concluído',
                'html'     => 'Os registros selecionados foram encerrados com sucesso.',
                'timer'    => 5000,
            ]);
            $this->dispatchBrowserEvent('hideModal');
            $this->emit('refresh_accomany');
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao encerrar em massa',
                'html'     => "Ocorreu um erro ao tentar encerrar os registros. <br>
                    <p class='text-bg-light mt-2 p-2'>
                        Por favor, tente novamente mais tarde ou entre em contato com o suporte.
                    </p>",
            ]);
        }
    }
}
