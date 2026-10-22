<?php

namespace App\Models;

use App\Services\WorkReports\WorkReportFinalScopeResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class WorkReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'company_id',
        'user_id',
        'date',
        'equipment',
        'connection',
        'changes',
        'observation',
        'damage',
        'description',
        'team',
        'responsible',
        'approved',
        'rejected',
        'retry',
        'canceled',
        'canceled_at',
        'canceled_by',
        'dd',
        'informer',
        'informed_at',
        'acceptance_accepted',
        'acceptance_at',
        'acceptance_name',
        'acceptance_meta',
        'selected_final_scopes',
        'current_status_key',
        'current_status_label',
        'current_status_class',
        'current_status_updated_at',
    ];

    protected $casts = [
        'approved'                  => 'boolean',
        'rejected'                  => 'boolean',
        'canceled'                  => 'boolean',
        'canceled_at'               => 'datetime',
        'informed_at'               => 'datetime',
        'ads_due_at'                => 'datetime',
        'acceptance_accepted'       => 'boolean',
        'acceptance_at'             => 'datetime',
        'acceptance_meta'           => 'array',
        'selected_final_scopes'     => 'array',
        'retry'                     => 'boolean',
        'date'                      => 'date',
        'current_status_updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (WorkReport $workReport) {
            app(\App\Services\WorkReports\WorkReportCurrentStatusRefresher::class)->refresh($workReport);
            app(\App\Services\Ads\AdsDeadlinePolicy::class)->syncWorkReport($workReport);
        });

        static::updated(function (WorkReport $workReport) {
            if (!$workReport->wasChanged(['canceled', 'rejected', 'informed_at'])) {
                return;
            }

            app(\App\Services\WorkReports\WorkReportCurrentStatusRefresher::class)->refresh($workReport);
            app(\App\Services\Ads\AdsDeadlinePolicy::class)->syncWorkReport($workReport);
        });
    }

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function FiveNote()
    {
        return $this->hasOne(FiveNote::class, 'work_report_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function Company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function Equipment()
    {
        return $this->hasMany(Equipment::class)->orderBy('type')->orderBy('patrimony');
    }

    public function Orders()
    {
        return $this->belongsToMany(Order::class, 'order_work_report');
    }

    public function Meeters()
    {
        return $this->hasMany(Meeter::class);
    }

    public function Returnwork()
    {
        return $this->hasMany(ReturnWork::class);
    }

    public function LatestReturnwork()
    {
        return $this->hasOne(ReturnWork::class)->latestOfMany();
    }

    public function Adsform()
    {
        return $this->hasOne(Adsform::class);
    }

    public function AdsNonWorkingDayAdjustments()
    {
        return $this->hasMany(AdsNonWorkingDayAdjustment::class);
    }

    public function FlowProductions()
    {
        return $this->hasMany(WorkReportFlowProduction::class);
    }

    public function Files()
    {
        return $this->morphToMany(File::class, 'fileable')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('canceled', false);
    }

    public function scopePendingRejectedForPartner(Builder $query): Builder
    {
        return $query->active()
            ->where('rejected', true);
    }

    // Agragações customizadas

    public function getEarliestFimRealAttribute()
    {
        // 1) Junta pivot e operations
        $minDateTime = DB::table('order_work_report as p')
            ->join('operations as o', 'p.order_id', '=', 'o.order_id')
            // 2) Filtra só o nosso work_report
            ->where('p.work_report_id', $this->id)
            // 3) Operação 0020
            ->where('o.operacao', '0020')
            // 4) Busca o mínimo de fimReal
            ->min('o.fimReal');

        if (!$minDateTime) {
            return null;
        }

        // 5) Converte para só data (YYYY-MM-DD)
        return Carbon::parse($minDateTime);
    }

    public function finalScopePayloads(): array
    {
        $orders = $this->relationLoaded('Orders') ? $this->Orders : $this->Orders()->get();

        return $this->filterFinalScopePayloads(
            app(WorkReportFinalScopeResolver::class)->resolve($this->Note?->type_note, $orders)
        );
    }

    /** Nome amigável do informe para telas de cancelamento/descancelamento. */
    public function displayLabel(): string
    {
        $scopes = $this->selectedFinalScopesOrNull();

        $scopeLabel = $scopes
            ? collect($scopes)->map(fn ($scope) => $this->finalScopeLabel($scope))->implode(' + ')
            : 'Geral';

        return "Informe #{$this->id} - {$scopeLabel}";
    }

    public function finalScopeBadges(): array
    {
        return collect($this->finalScopePayloads())
            ->map(fn (array $payload) => [
                'scope' => $payload['scope'],
                'label' => $this->finalScopeLabel($payload['scope']),
                'class' => $this->finalScopeBadgeClass($payload['scope']),
            ])
            ->values()
            ->all();
    }

    public function finalScopeLabel(string $scope): string
    {
        return match ($scope) {
            WorkReportFinalScopeResolver::SCOPE_NETWORK    => 'Rede',
            WorkReportFinalScopeResolver::SCOPE_CONNECTION => 'Ligacao',
            default                                        => 'Geral',
        };
    }

    public function finalScopeBadgeClass(string $scope): string
    {
        return match ($scope) {
            WorkReportFinalScopeResolver::SCOPE_NETWORK    => 'text-bg-primary',
            WorkReportFinalScopeResolver::SCOPE_CONNECTION => 'text-bg-warning',
            default                                        => 'text-bg-secondary',
        };
    }

    public function selectedFinalScopesOrNull(): ?array
    {
        if (!is_array($this->selected_final_scopes)) {
            return null;
        }

        $selected = collect($this->selected_final_scopes)
            ->map(fn ($scope) => (string) $scope)
            ->intersect([
                WorkReportFinalScopeResolver::SCOPE_NETWORK,
                WorkReportFinalScopeResolver::SCOPE_CONNECTION,
            ])
            ->unique()
            ->values()
            ->all();

        return empty($selected) ? null : $selected;
    }

    private function filterFinalScopePayloads(array $payloads): array
    {
        $selected = $this->selectedFinalScopesOrNull();

        if ($selected === null) {
            return $payloads;
        }

        $filtered = collect($payloads)
            ->filter(fn (array $payload) => in_array($payload['scope'] ?? null, $selected, true))
            ->values()
            ->all();

        return empty($filtered) ? $payloads : $filtered;
    }
}
