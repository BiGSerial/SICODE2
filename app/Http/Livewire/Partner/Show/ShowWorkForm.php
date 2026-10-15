<?php

namespace App\Http\Livewire\Partner\Show;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\{File, WorkReport};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ShowWorkForm extends Component
{
    use AuthorizesPartnerAccess;

    public ?WorkReport $form = null;

    protected $listeners = [
        'show_form',
    ];

    public function show_form(WorkReport $form)
    {
        $query = WorkReport::query()->whereKey($form->id);

        if (!$this->userCanInspectInternalReports()) {
            $this->authorizePartnerAccess('conclusion_reports.list');
            $this->applyPartnerCompanyScope($query);
            $this->applyPartnerBranchScopeToNoteRelation($query);
        }

        $this->form = $query->firstOrFail()->load([
            'Company',
            'Equipment',
            'Meeters',
            'Note.FiveNote.productions.Service:id,uuid,service',
            'Note.FiveNote.productions.User:id,name,email',
            'Orders',
            'FlowProductions' => function ($query) {
                $query->with([
                    'Production.Service:id,uuid,service',
                    'Production.User:id,name,email',
                    'Production.Company:id,name',
                    'LinkedBy:id,name,email',
                ])
                    ->orderBy('stage')
                    ->orderByDesc('is_current')
                    ->orderBy('linked_at')
                    ->orderBy('id');
            },
        ]);

        // dd($this->form);

        if ($this->form) {

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_form_work',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.partner.show.show-work-form', [
            'reportFiles' => $this->form ? $this->filesOfReport($this->form) : collect(),
        ]);
    }

    /**
     * Arquivos exclusivos do informe: os das produções vinculadas a ele (tanto pelo vínculo legado
     * `file_production` quanto pelo polimórfico `fileables`) e os anexados ao próprio informe.
     * Nunca os demais arquivos da nota, que pertencem a outros informes e serviços.
     *
     * @return Collection<int, File>
     */
    private function filesOfReport(WorkReport $report): Collection
    {
        $productionIds = $report->FlowProductions->pluck('production_id')->filter()->unique()->values()->all();
        $ownFileIds    = $report->Files()->pluck('files.id')->all();

        if (empty($productionIds) && empty($ownFileIds)) {
            return collect();
        }

        return File::query()
            ->with('Service')
            ->where(function ($query) use ($productionIds, $ownFileIds) {
                $query->whereIn('files.id', $ownFileIds);

                if ($productionIds) {
                    $query->orWhereHas('Productions', fn ($q) => $q->whereIn('productions.id', $productionIds))
                        ->orWhereHas('MorphProductions', fn ($q) => $q->whereIn('productions.id', $productionIds));
                }
            })
            ->get();
    }

    private function userCanInspectInternalReports(): bool
    {
        $user = auth()->user();

        return ($user && !$user->onlyparner)
            || Gate::allows('management')
            || Gate::allows('admin')
            || Gate::allows('superadm');
    }
}
