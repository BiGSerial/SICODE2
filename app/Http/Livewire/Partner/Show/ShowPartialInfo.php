<?php

namespace App\Http\Livewire\Partner\Show;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\{File, Partial};
use App\Services\Files\FileStorageService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ShowPartialInfo extends Component
{
    use AuthorizesPartnerAccess;

    public ?Partial $form = null;

    protected $listeners = [
        'show_form',
    ];

    public function show_form(Partial $form)
    {
        $query = Partial::query()->whereKey($form->id);

        if (!$this->userCanInspectInternalReports()) {
            $this->authorizePartnerAccess('partial_reports.show');
            $this->applyPartnerCompanyScope($query);
            $this->applyPartnerBranchScopeToNoteRelation($query);
        }

        $this->form = $query->firstOrFail()->load(['Note.Orders', 'Orders', 'Company', 'User', 'Engineer', 'Supervisor', 'Payer', 'Files.Service']);

        // dd($this->form);

        if ($this->form) {

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_partial_info',
            ]);
        }
    }

    public function downloadFile(File $file)
    {
        $this->authorizePartnerAccess('partial_reports.show');

        return app(FileStorageService::class)->download($file, $file->stored_name);
    }

    public function render()
    {
        return view('livewire.partner.show.show-partial-info');
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
