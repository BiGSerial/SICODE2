<?php

namespace App\Http\Livewire\Partner\Show;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\WorkReport;
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
            'Note.Files.Service',
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
        return view('livewire.partner.show.show-work-form');
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
