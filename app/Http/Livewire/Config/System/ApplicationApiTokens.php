<?php

namespace App\Http\Livewire\Config\System;

use App\Models\ApplicationApiToken;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

class ApplicationApiTokens extends Component
{
    public string $name = '';
    public ?string $generatedToken = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
        ];
    }

    public function mount(): void
    {
        abort_unless(Gate::allows('admin'), 403);
    }

    public function createToken(): void
    {
        $this->validate();

        $plainToken = 'sicode_' . Str::random(64);
        $token = ApplicationApiToken::create([
            'created_by_user_id' => auth()->id(),
            'name' => trim($this->name),
            'token_prefix' => substr($plainToken, 0, 16),
            'token_hash' => hash('sha256', $plainToken),
            'active' => true,
        ]);

        $this->generatedToken = $plainToken;
        $this->name = '';
        session()->flash('message', "Token #{$token->id} criado. Copie-o agora; ele não será exibido novamente.");
    }

    public function revoke(int $id): void
    {
        ApplicationApiToken::query()
            ->whereKey($id)
            ->where('active', true)
            ->update([
                'active' => false,
                'revoked_at' => now(),
            ]);

        session()->flash('message', 'Token revogado com sucesso.');
    }

    public function clearGeneratedToken(): void
    {
        $this->generatedToken = null;
    }

    public function render()
    {
        return view('livewire.config.system.application-api-tokens', [
            'tokens' => ApplicationApiToken::with('createdBy')->latest()->get(),
            'audits' => \App\Models\ApplicationApiAudit::with('token', 'createdBy')->latest()->limit(50)->get(),
        ]);
    }
}
