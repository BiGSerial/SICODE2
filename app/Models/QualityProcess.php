<?php

namespace App\Models;

use App\Enum\{QualityProcessState, QualityProcessStatus, QualityStageLevel, QualityStageType};
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityProcess extends Model
{
    use HasFactory;

    protected $fillable = ['production_id', 'note_id', 'company_id', 'n1_user_id', 'original_designer_id', 'current_designer_id', 'status', 'state', 'state_changed_at', 'phase', 'round_number', 'current_stage', 'dispatched_by', 'dispatched_at', 'completed_by', 'completed_at'];

    protected $casts = ['status' => QualityProcessStatus::class, 'state' => QualityProcessState::class, 'phase' => QualityStageType::class, 'current_stage' => QualityStageLevel::class, 'dispatched_at' => 'datetime', 'state_changed_at' => 'datetime', 'completed_at' => 'datetime'];

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }
    public function Note()
    {
        return $this->belongsTo(Note::class);
    }
    public function Company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }
    public function OriginalDesigner()
    {
        return $this->belongsTo(User::class, 'original_designer_id')->withTrashed();
    }
    public function CurrentDesigner()
    {
        return $this->belongsTo(User::class, 'current_designer_id')->withTrashed();
    }
    public function N1User()
    {
        return $this->belongsTo(User::class, 'n1_user_id')->withTrashed();
    }
    public function SapRequests()
    {
        return $this->hasMany(QualitySapRequest::class)->orderBy('id');
    }
    public function DispatchedBy()
    {
        return $this->belongsTo(User::class, 'dispatched_by')->withTrashed();
    }
    public function CompletedBy()
    {
        return $this->belongsTo(User::class, 'completed_by')->withTrashed();
    }
    public function Stages()
    {
        return $this->hasMany(QualityStage::class)->orderBy('round_number')->orderBy('id');
    }
    public function Events()
    {
        return $this->hasMany(QualityEvent::class)->orderBy('created_at')->orderBy('id');
    }
    public function Rejections()
    {
        return $this->hasMany(QualityRejection::class)->latest();
    }
    /**
     * Posição na jornada de 7 etapas (0..6) ou 7 = concluída:
     * 1º ciclo: 0 despacho, 1 execução, 2 análise N1, 3 decisão N2 · 2º ciclo: 4 execução, 5 análise N1, 6 decisão N2.
     */
    public function flowIndex(): int
    {
        if ($this->state === QualityProcessState::COMPLETED) {
            return 7;
        }

        $step = match ($this->state) {
            QualityProcessState::AWAITING_N1_DISPATCH => 0,
            QualityProcessState::AWAITING_DESIGNER    => 1,
            QualityProcessState::AWAITING_N1_REVIEW, QualityProcessState::N2_RETURNED => 2,
            default => 3,
        };

        return $this->phase === QualityStageType::PROJECT ? $step : 3 + $step;
    }

    /** Urgência pelo tempo no estado atual: ok < 2 dias, warn 2-4, late 5+. */
    public function urgency(): string
    {
        if ($this->status !== \App\Enum\QualityProcessStatus::ACTIVE || !$this->state_changed_at) {
            return 'ok';
        }

        $days = $this->state_changed_at->diffInDays(now());

        return $days >= 5 ? 'late' : ($days >= 2 ? 'warn' : 'ok');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', QualityProcessStatus::ACTIVE->value);
    }
    /** Escopo de visibilidade por papel. Designer enxerga só processos em que foi designado. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(\App\Services\Quality\QualityRoles::class)->scopeVisible($query, $user);
    }
}
