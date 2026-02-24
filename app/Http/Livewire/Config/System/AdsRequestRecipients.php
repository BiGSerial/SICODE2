<?php

namespace App\Http\Livewire\Config\System;

use App\Models\AdsRequestDefaultUser;
use App\Models\Service;
use App\Models\SystemSetting;
use App\Models\User;
use Livewire\Component;

class AdsRequestRecipients extends Component
{
    public string $search = '';
    public ?string $selectedUserId = null;
    public ?string $selectedServiceId = null;
    public bool $testMode = false;

    public function mount(): void
    {
        $this->testMode = SystemSetting::getBool('ads_auto_test_mode', false);
        $this->selectedServiceId = SystemSetting::getValue('ads_auto_default_service_id');
    }

    public function addRecipient(): void
    {
        if (!$this->selectedUserId) {
            return;
        }

        AdsRequestDefaultUser::updateOrCreate(
            ['user_id' => $this->selectedUserId],
            [
                'active' => true,
                'created_by' => auth()->id(),
            ]
        );

        $this->selectedUserId = null;
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Usuário adicionado.',
            'timer' => 1800,
        ]);
    }

    public function removeRecipient(int $id): void
    {
        AdsRequestDefaultUser::whereKey($id)->delete();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Usuário removido.',
            'timer' => 1800,
        ]);
    }

    public function updatedTestMode(bool $value): void
    {
        SystemSetting::setValue('ads_auto_test_mode', $value ? '1' : '0');
    }

    public function updatedSelectedServiceId($value): void
    {
        $value = $value ?: null;

        if ($value !== null && !Service::query()->where('uuid', $value)->exists()) {
            $this->selectedServiceId = null;
            SystemSetting::setValue('ads_auto_default_service_id', null);
            return;
        }

        SystemSetting::setValue('ads_auto_default_service_id', $value);
    }

    public function getCandidatesProperty()
    {
        $search = trim($this->search);

        return User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'email']);
    }

    public function getRecipientsProperty()
    {
        return AdsRequestDefaultUser::query()
            ->with('user:id,name,email')
            ->orderByDesc('id')
            ->get();
    }

    public function getServiceOptionsProperty()
    {
        return Service::query()
            ->orderBy('service')
            ->get(['uuid', 'service']);
    }

    public function render()
    {
        return view('livewire.config.system.ads-request-recipients', [
            'candidates' => $this->candidates,
            'recipients' => $this->recipients,
            'serviceOptions' => $this->serviceOptions,
        ]);
    }
}
