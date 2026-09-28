<?php

namespace App\Services\Dispatch;

use App\Models\Note;
use App\Models\Production;
use App\Models\Service;
use App\Models\Wpa;
use App\Support\SicodeRules;
use Illuminate\Support\Collection;

class DdAssignmentService
{
    public function required(Service|string $service): bool
    {
        $serviceKey = $service instanceof Service
            ? app(DispatchContextResolver::class)->serviceKey($service)
            : $service;

        return match ($serviceKey) {
            'survey' => SicodeRules::requiresDdForSurveyDispatch(),
            'supervision' => SicodeRules::requiresDdForSupervisionDispatch(),
            default => false,
        };
    }

    public function normalize(?string $dd): ?string
    {
        $dd = trim((string) $dd);

        return $dd !== '' ? $dd : null;
    }

    public function assign(Note $note, Production $production, ?string $dd): ?Wpa
    {
        $dd = $this->normalize($dd);

        if (!$dd) {
            return null;
        }

        $existing = Wpa::query()->where('dd', $dd)->lockForUpdate()->get();
        $foreign = $existing->first(fn (Wpa $wpa) => (string) $wpa->note_id !== (string) $note->id);

        if ($foreign) {
            throw new DispatchException("DD {$dd} ja foi associada a outra Nota/OV.");
        }

        $current = $existing->first(fn (Wpa $wpa) => (int) $wpa->production_id === (int) $production->id);
        if ($current) {
            return $current;
        }

        $unassigned = $existing->first(fn (Wpa $wpa) => is_null($wpa->production_id));
        if ($unassigned) {
            $unassigned->update([
                'production_id' => $production->id,
                'service_id' => $production->service_id,
                'note_id' => $note->id,
            ]);

            return $unassigned->fresh();
        }

        return Wpa::create([
            'production_id' => $production->id,
            'note_id' => $note->id,
            'service_id' => $production->service_id,
            'dd' => $dd,
        ]);
    }

    public function forProduction(Production $production): ?Wpa
    {
        return $production->Wpas()->latest('id')->first();
    }

    public function forNote(Note $note, ?string $serviceId = null): Collection
    {
        $query = $note->Wpas()->whereNotNull('dd');

        if ($serviceId) {
            $query->where(function ($q) use ($serviceId) {
                $q->where('service_id', $serviceId)->orWhereNull('service_id');
            });
        }

        return $query->latest('id')->get();
    }
}
