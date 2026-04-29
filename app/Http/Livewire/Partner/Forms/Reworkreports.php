<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Reworkreports extends Workreports
{
    public ?WorkReport $workReport = null;
    public bool $reinform = true;
    public bool $hasExistingAds = false;
    public bool $hasTacitAds = false;
    public bool $hasPendingFiles = false;
    public $keepExistingAds = null;
    public array $acceptanceHistory = [];
    public array $existingFileTypes = ['ASBUILT', 'CROQUI', 'EVIDENCIA', 'FTVEO', 'IMAGEM', 'LISTA', 'PROJETO', 'OUTROS'];

    protected $listeners = [
        'confirm_informe',
        'send_informe',
        'hasFile',
        'hasPendingFile',
        'savedFiles',
    ];

    public function mount(?string $token = null)
    {
        if (!$token) {
            abort(403);
        }

        $payload = session()->get("partner_reinform_work_report.{$token}");

        if (!$payload || empty($payload['work_report_id']) || empty($payload['created_at'])) {
            abort(403);
        }

        if (now()->timestamp - (int) $payload['created_at'] > 1800) {
            session()->forget("partner_reinform_work_report.{$token}");
            abort(403);
        }

        $this->loadCompanies();

        $this->workReport = WorkReport::query()
            ->with(['Note.Orders', 'Orders', 'Equipment', 'Meeters', 'Returnwork.User', 'Adsform'])
            ->when(!auth()->user()->superadm, function ($q) {
                $q->where(function ($query) {
                    $query->whereIn('company_id', auth()->user()->Companies->pluck('id')->toArray())
                        ->orWhere('company_id', auth()->user()->Company->id);
                });
            })
            ->where('rejected', true)
            ->findOrFail((int) $payload['work_report_id']);

        $this->note = $this->workReport->Note;
        $this->hasExistingAds = (bool) $this->workReport->Adsform;
        $this->hasTacitAds = (bool) ($this->workReport->Adsform?->tacit ?? false);
        $this->backfillWorkReportFiles();
        $this->hasFiles = $this->hasExistingInformeFiles();
        $this->loadExistingData();
    }

    public function submit()
    {
        if (!$this->canInformNote($this->note)) {
            return;
        }

        if ($this->requireFilesForSubmit && !$this->hasFiles) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Arquivos Obrigatórios',
                'html'     => 'Este informe precisa ter ao menos um arquivo vinculado antes do reenvio.',
            ]);
            return;
        }

        if ($this->hasExistingAds && !$this->hasTacitAds && $this->keepExistingAds === null) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Informe a decisão sobre a ADS',
                'html'     => 'Selecione se deseja manter ou remover a ADS já associada ao informe.',
            ]);
            return;
        }

        try {
            $this->validate();

            if ($this->form['equipment'] == true && empty($this->temp_equipment)) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Erros de Validação',
                    'html'     => 'Os equipamentos instalados/desinstalados são obrigatórios.',
                ]);
                return;
            }

            if ($this->form['damage'] == true && !trim((string) $this->form['description'])) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Erros de Validação',
                    'html'     => 'O detalhamento dos danos causados é obrigatório.',
                ]);
                return;
            }

            if ($this->form['changes'] == true && !$this->hasFiles) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Erros de Validação',
                    'html'     => 'É obrigatório anexar o AsBuilt da obra executada.',
                ]);
                return;
            }

            if ($this->meeters == true && empty($this->temp_meeters)) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Erros de Validação',
                    'html'     => 'É obrigatório informar os medidores instalados.',
                ]);
                return;
            }

            $adsMessage = '';
            if ($this->hasTacitAds) {
                $adsMessage = '<p>Este informe possui ADS tácita. O vencimento de uma ADS tácita não pode ser alterado pelo reenvio do informe.</p>';
            } elseif ($this->hasExistingAds) {
                $adsMessage = $this->asBool($this->keepExistingAds)
                    ? '<p>A ADS existente será mantida. A data de entrega da ADS será atualizada para a data deste reenvio e o prazo de fiscalização será recalculado a partir da nova data.</p>'
                    : '<p>A ADS existente será removida. O usuário terá 6 dias, contados a partir deste reenvio, para entregar uma nova ADS na aba <strong>Entregar ADS</strong>.</p>';
            }

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'REENVIAR INFORME ' . $this->note->note,
                'msg'           => '<div class="card"><div class="card-body text-start">
                    <p>Você está prestes a reenviar este informe de obra. A data de envio será atualizada para agora.</p>
                    ' . $adsMessage . '
                    <p><strong>Confirma o reenvio do informe?</strong></p>
                </div></div>',
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, reenviar',
                'btnCanceltxt'  => 'Cancelar',
                'action'        => 'send_informe',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhum informe foi reenviado.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $html = '<ul>';
            foreach ($e->validator->errors()->all() as $error) {
                $html .= '<li>' . $error . '</li>';
            }
            $html .= '</ul>';

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => '<div class="card"><div class="card-body text-start">' . $html . '</div></div>',
            ]);
        }
    }

    public function send_informe()
    {
        if (!$this->workReport || !$this->canInformNote($this->note)) {
            return;
        }

        $informedAt = now();

        DB::beginTransaction();

        try {
            $this->form['note_id'] = $this->note->id;
            $this->form['company_id'] = $this->workReport->company_id;
            $this->form['user_id'] = auth()->id();
            $this->form['informed_at'] = $informedAt;
            $this->form['rejected'] = false;
            $this->form['acceptance_accepted'] = true;
            $this->form['acceptance_at'] = $informedAt;
            $this->form['acceptance_meta'] = $this->mergeAcceptanceMeta();

            $this->workReport->fill($this->form);
            $this->workReport->save();

            $this->workReport->Orders()->sync(collect($this->temp_orders)->pluck('id')->all());

            $this->workReport->Equipment()->delete();
            if ($this->workReport->equipment && !empty($this->temp_equipment)) {
                foreach ($this->temp_equipment as $equipment) {
                    $this->workReport->Equipment()->create($equipment);
                }
            }

            $this->workReport->Meeters()->delete();
            if (!empty($this->temp_meeters)) {
                foreach ($this->temp_meeters as $meeter) {
                    $this->workReport->Meeters()->create($meeter);
                }
            }

            $this->syncAdsAfterReinform($informedAt);

            DB::commit();

            if ($this->hasPendingFiles) {
                $this->emitTo('files.manager.create-gen-files', 'saveFiles');
                return;
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Informe reenviado com sucesso',
            ]);

            return redirect()->route('partner.report.rejectedWorked');
        } catch (\Throwable $th) {
            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao reenviar informe',
                'html'     => $th->getMessage(),
            ]);
        }
    }

    public function savedFiles()
    {
        $this->emitTo('files.manager.create-gen-files', 'cleanFiles');

        return redirect()->route('partner.report.rejectedWorked');
    }

    public function hasPendingFile(bool $hasPendingFile)
    {
        $this->hasPendingFiles = $hasPendingFile;
    }

    public function calcelForm()
    {
        return redirect()->route('partner.report.rejectedWorked');
    }

    protected function loadExistingData(): void
    {
        $this->form = [
            'note_id' => $this->workReport->note_id,
            'company_id' => $this->workReport->company_id,
            'user_id' => $this->workReport->user_id,
            'date' => optional($this->workReport->date)->format('Y-m-d'),
            'equipment' => $this->workReport->equipment,
            'connection' => $this->workReport->connection,
            'changes' => $this->workReport->changes,
            'observation' => $this->workReport->observation,
            'damage' => $this->workReport->damage,
            'description' => $this->workReport->description,
            'team' => $this->workReport->team,
            'dd' => $this->workReport->dd,
            'responsible' => $this->workReport->responsible,
            'informer' => $this->workReport->informer,
            'acceptance_accepted' => false,
            'acceptance_name' => null,
        ];

        $this->temp_orders = $this->workReport->Orders
            ->mapWithKeys(fn ($order) => [$order->id => ['id' => $order->id, 'ordem' => $order->ordem]])
            ->all();

        $this->temp_equipment = $this->workReport->Equipment
            ->map(fn ($equipment) => [
                'type' => $equipment->type,
                'patrimony' => $equipment->patrimony,
                'fases' => $equipment->fases,
                'pole' => $equipment->pole,
                'installed' => $equipment->installed,
            ])
            ->all();

        $this->temp_meeters = $this->workReport->Meeters
            ->map(fn ($meeter) => [
                'number' => $meeter->number,
                'borne' => $meeter->borne,
                'fases' => $meeter->fases,
            ])
            ->all();

        $this->meeters = !empty($this->temp_meeters);
        $this->acceptanceHistory = $this->extractAcceptanceHistory();
    }

    protected function mergeAcceptanceMeta(): array
    {
        $newMeta = $this->buildAcceptanceMeta();
        $newMeta['acceptance_name'] = $this->form['acceptance_name'];
        $newMeta['acceptance_at'] = now()->toDateTimeString();
        $newMeta['event'] = 'reinform';

        $existing = $this->workReport->acceptance_meta;
        $history = [];

        if (is_array($existing)) {
            $history = is_array($existing['history'] ?? null) ? $existing['history'] : [];
            $current = $existing['current'] ?? $existing;

            if (!empty($current)) {
                $current['acceptance_name'] = $this->workReport->acceptance_name;
                $current['acceptance_at'] = optional($this->workReport->acceptance_at)->toDateTimeString();
                $history[] = $current;
            }
        }

        $history[] = $newMeta;

        return [
            'current' => $newMeta,
            'history' => $history,
        ];
    }

    protected function extractAcceptanceHistory(): array
    {
        $meta = $this->workReport->acceptance_meta;

        if (!is_array($meta)) {
            return [];
        }

        if (is_array($meta['history'] ?? null)) {
            return $meta['history'];
        }

        return [$meta];
    }

    protected function syncAdsAfterReinform(Carbon $informedAt): void
    {
        $adsForm = $this->workReport->Adsform()->first();

        if (!$adsForm) {
            return;
        }

        if ($adsForm->tacit) {
            return;
        }

        if ($this->asBool($this->keepExistingAds)) {
            $adsForm->forceFill([
                'created_at' => $informedAt,
                'updated_at' => now(),
                'tacit_due_at' => $informedAt->copy()->addDays(6)->endOfDay(),
            ])->save();

            return;
        }

        $adsForm->Files()->detach();
        $adsForm->delete();
    }

    protected function asBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function hasExistingInformeFiles(): bool
    {
        if (!$this->workReport) {
            return false;
        }

        return $this->workReport->Files()
            ->where('user_id', auth()->id())
            ->where(function ($q) {
                foreach ($this->existingFileTypes as $type) {
                    $q->orWhere('file_name', 'like', $type . '\_%');
                }
            })
            ->exists();
    }

    protected function backfillWorkReportFiles(): void
    {
        if (!$this->workReport || !$this->note) {
            return;
        }

        $fileIds = $this->note->Files()
            ->where('user_id', auth()->id())
            ->where(function ($q) {
                foreach ($this->existingFileTypes as $type) {
                    $q->orWhere('file_name', 'like', $type . '\_%');
                }
            })
            ->pluck('files.id')
            ->all();

        if (!empty($fileIds)) {
            $this->workReport->Files()->syncWithoutDetaching($fileIds);
        }
    }

    public function render()
    {
        return view('livewire.partner.forms.workreports');
    }
}
