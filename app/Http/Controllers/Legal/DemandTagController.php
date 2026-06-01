<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandNoteInstruction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DemandTagController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'demand_id' => ['required', 'integer', 'exists:legal_demands,id'],
            'note_id' => ['required', 'integer', 'exists:notes,id'],
            'operator_sla_due_at' => ['nullable', 'date'],
            'operator_sla_note' => ['nullable', 'string', 'max:2000'],
            'instruction' => ['nullable', 'string', 'max:5000'],
        ]);

        /** @var LegalDemand $demand */
        $demand = LegalDemand::query()->findOrFail((int) $payload['demand_id']);

        $demand->operator_sla_due_at = $payload['operator_sla_due_at'] ?? null;
        $demand->operator_sla_note = trim((string) ($payload['operator_sla_note'] ?? '')) ?: null;
        $demand->save();

        $instruction = trim((string) ($payload['instruction'] ?? ''));
        if ($instruction !== '') {
            LegalDemandNoteInstruction::query()
                ->where('legal_demand_id', $demand->id)
                ->where('note_id', (int) $payload['note_id'])
                ->where('active', true)
                ->update(['active' => false]);

            LegalDemandNoteInstruction::query()->create([
                'legal_demand_id' => $demand->id,
                'note_id' => (int) $payload['note_id'],
                'created_by' => auth()->id(),
                'instruction' => $instruction,
                'active' => true,
            ]);
        }

        return back()->with('success', 'Dados da demanda atualizados.');
    }
}

