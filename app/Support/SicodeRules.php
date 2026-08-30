<?php

namespace App\Support;

use App\Models\{Company, Note, Production, User};
use Illuminate\Support\Collection;

class SicodeRules
{
    public static function ruleset(): string
    {
        return (string) config('sicode.ruleset', 'es');
    }

    public static function displayName(string $fallback = 'sicode'): string
    {
        return (string) config('sicode.display_name', $fallback);
    }

    public static function requiresDdForSurveyDispatch(): bool
    {
        return self::boolRule('dispatch.survey.requires_dd', true);
    }

    public static function requiresDdForSupervisionDispatch(): bool
    {
        return self::boolRule('dispatch.supervision.requires_dd', true);
    }

    public static function allowsCompanyStackDispatch(): bool
    {
        return self::boolRule('dispatch.allows_company_stack', true);
    }

    public static function partnerCanClaimCompanyStack(): bool
    {
        return self::boolRule('dispatch.partner_can_claim_company_stack', true);
    }

    public static function workReportFieldEnabled(string $field): bool
    {
        return self::boolRule("work_report.fields.{$field}", true);
    }

    public static function workReportDdMode(): string
    {
        $mode = (string) config('sicode.rules.' . self::ruleset() . '.work_report.dd_mode', 'required');

        return in_array($mode, ['required', 'optional', 'hidden'], true) ? $mode : 'required';
    }

    public static function workReportRequiresFiles(): bool
    {
        return self::boolRule('work_report.requires_files', true);
    }

    public static function workReportBlocksByNoteStatus(): bool
    {
        return self::boolRule('work_report.blocks_by_note_status', false);
    }

    public static function workReportSplitsBtzeroEpFinalFlows(): bool
    {
        return self::boolRule('work_report.split_btzero_ep_final_flows', false);
    }

    public static function workReportFinalScopeOrderPrefixes(string $scope): array
    {
        $prefixes = config('sicode.rules.' . self::ruleset() . ".work_report.final_scope_order_prefixes.{$scope}", []);

        if (!is_array($prefixes)) {
            return [];
        }

        return collect($prefixes)
            ->map(fn ($prefix) => preg_replace('/\D+/', '', (string) $prefix))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function visibleCompanyIdsFor(User $user): array
    {
        return self::visibleCompanyIdsCollectionFor($user)->all();
    }

    public static function userCanAccessCompany(User $user, ?string $companyId): bool
    {
        if (!$companyId) {
            return false;
        }

        if (!$user->contract) {
            return true;
        }

        return self::visibleCompanyIdsCollectionFor($user)->contains($companyId);
    }

    public static function primaryCompanyNameFor(User $user): ?string
    {
        if (!$user->contract) {
            return null;
        }

        $companyId = $user->Employee?->Contract?->company_id
            ?: $user->company_id
            ?: self::visibleCompanyIdsCollectionFor($user)->first();

        if (!$companyId) {
            return null;
        }

        return Company::where('id', $companyId)->value('name');
    }

    public static function applyContractDispatchListVisibility($query, User $user, string $serviceId)
    {
        if (!$user->contract) {
            return $query;
        }

        $companyIds = self::visibleCompanyIdsFor($user);

        if (!count($companyIds)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas('Productions', function ($q) use ($companyIds, $serviceId) {
            $q->where('service_id', $serviceId)
                ->whereIn('company_id', $companyIds)
                ->where('completed', false);
        });
    }

    public static function hasCompanyDispatchProductionFor(Note $note, User $user, string $serviceId): bool
    {
        if (!$user->contract) {
            return true;
        }

        $companyIds = self::visibleCompanyIdsFor($user);

        if (!count($companyIds)) {
            return false;
        }

        if ($note->relationLoaded('Productions')) {
            return $note->Productions
                ->where('service_id', $serviceId)
                ->whereIn('company_id', $companyIds)
                ->where('completed', false)
                ->isNotEmpty();
        }

        return $note->Productions()
            ->where('service_id', $serviceId)
            ->whereIn('company_id', $companyIds)
            ->where('completed', false)
            ->exists();
    }

    public static function applyContractDispatchMainVisibility($query, User $user, string $serviceId, callable $applyStatusRules)
    {
        if (!$user->contract) {
            $applyStatusRules($query);

            return $query;
        }

        $companyIds = self::visibleCompanyIdsFor($user);

        if (!count($companyIds)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function ($visibility) use ($applyStatusRules, $companyIds, $serviceId) {
            $visibility->where(function ($statusRules) use ($applyStatusRules) {
                $applyStatusRules($statusRules);
            })->orWhereHas('Productions', function ($production) use ($companyIds, $serviceId) {
                $production->where('service_id', $serviceId)
                    ->whereIn('company_id', $companyIds)
                    ->where('completed', false);
            });
        })->whereHas('Productions', function ($production) use ($companyIds, $serviceId) {
            $production->where('service_id', $serviceId)
                ->whereIn('company_id', $companyIds)
                ->where('completed', false);
        });
    }

    public static function openCompanyStackProductionFor(Note $note, User $user, string $serviceId): ?Production
    {
        if (!$user->contract) {
            return null;
        }

        $companyIds = self::visibleCompanyIdsFor($user);

        if (!count($companyIds)) {
            return null;
        }

        if ($note->relationLoaded('Productions')) {
            return $note->Productions
                ->where('service_id', $serviceId)
                ->whereIn('company_id', $companyIds)
                ->whereNull('user_id')
                ->where('completed', false)
                ->where('confirmed', false)
                ->first();
        }

        return $note->Productions()
            ->where('service_id', $serviceId)
            ->whereIn('company_id', $companyIds)
            ->whereNull('user_id')
            ->where('completed', false)
            ->where('confirmed', false)
            ->first();
    }

    public static function dispatchDdFor(Note $note, string $serviceId, ?Production $production = null): ?string
    {
        $wpas = $note->relationLoaded('Wpas')
            ? $note->Wpas
            : $note->Wpas()->get();

        $wpas = $wpas->filter(fn ($wpa) => filled($wpa->dd));

        if ($production) {
            $dd = $wpas
                ->where('production_id', $production->id)
                ->sortByDesc('id')
                ->first()?->dd;

            if ($dd) {
                return $dd;
            }
        }

        $openProductionIds = $note->relationLoaded('Productions')
            ? $note->Productions
                ->where('service_id', $serviceId)
                ->where('completed', false)
                ->where('confirmed', false)
                ->pluck('id')
                ->all()
            : $note->Productions()
                ->where('service_id', $serviceId)
                ->where('completed', false)
                ->where('confirmed', false)
                ->pluck('id')
                ->all();

        if (count($openProductionIds)) {
            $dd = $wpas
                ->where('service_id', $serviceId)
                ->whereIn('production_id', $openProductionIds)
                ->sortByDesc('id')
                ->first()?->dd;

            if ($dd) {
                return $dd;
            }
        }

        $dd = $wpas
            ->where('service_id', $serviceId)
            ->whereNull('production_id')
            ->sortByDesc('id')
            ->first()?->dd;

        if ($dd) {
            return $dd;
        }

        return $wpas->sortByDesc('id')->first()?->dd;
    }

    private static function visibleCompanyIdsCollectionFor(User $user): Collection
    {
        $ids = collect([
            $user->company_id,
            $user->Employee?->Contract?->company_id,
        ]);

        if ($user->relationLoaded('Companies')) {
            $ids = $ids->merge($user->Companies->pluck('id'));
        } else {
            $ids = $ids->merge($user->Companies()->pluck('companies.id'));
        }

        return $ids
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
    }

    private static function boolRule(string $key, bool $default = false): bool
    {
        return (bool) config('sicode.rules.' . self::ruleset() . '.' . $key, $default);
    }
}
