<?php

namespace App\Models;

use App\Services\WorkReports\WorkReportFinalScopeResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
    ];

    protected $casts = [
        'approved' => 'boolean',
        'rejected' => 'boolean',
        'canceled' => 'boolean',
        'canceled_at' => 'datetime',
        'informed_at' => 'datetime',
        'acceptance_accepted' => 'boolean',
        'acceptance_at' => 'datetime',
        'acceptance_meta' => 'array',
        'selected_final_scopes' => 'array',
        'retry' => 'boolean',
        'date' => 'date',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
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
            ->where('rejected', true)
            ->whereDoesntHave('Note', function ($q) {
                $q->whereIn('nstats', [55])
                    ->orWhere(function ($q) {
                        $q->where('nstats', 99)
                            ->where('type_note', 1);
                    });
            });
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

        if (! $minDateTime) {
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
            WorkReportFinalScopeResolver::SCOPE_NETWORK => 'Rede',
            WorkReportFinalScopeResolver::SCOPE_CONNECTION => 'Ligacao',
            default => 'Geral',
        };
    }

    public function finalScopeBadgeClass(string $scope): string
    {
        return match ($scope) {
            WorkReportFinalScopeResolver::SCOPE_NETWORK => 'text-bg-primary',
            WorkReportFinalScopeResolver::SCOPE_CONNECTION => 'text-bg-warning',
            default => 'text-bg-secondary',
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
