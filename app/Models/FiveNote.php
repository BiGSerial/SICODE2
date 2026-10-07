<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, MorphToMany};

class FiveNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_d5',
        'note_id',
        'work_report_id',
        'loc_install',
        'conjunto',
        'pep',
        'e_pep',
        'codify',
        'company_id',
        'sintoms',
        'reason',
        'description',
        'name',
        'answered_by_user_id',
        'dispatch_at',
        'visible_partner',
        'is_completed',
        'completed_at',
        'is_supervisioned',
        'supervisioned_at',
        'is_payed',
        'payed_at',
        'is_archived',
        'isPassive',
        'returned',
    ];

    protected static function booted(): void
    {
        static::creating(function (FiveNote $fiveNote): void {
            if (!$fiveNote->work_report_id && !$fiveNote->isPassive) {
                $fiveNote->work_report_id = app(\App\Services\D5\D5WorkReportAssociationResolver::class)
                    ->closestFor($fiveNote)?->id;
            }

            $fiveNote->d5_origin_key = $fiveNote->originKey();
        });

        // O status materializado do informe depende do estado da D5: recalcula quando ele muda.
        static::updated(function (FiveNote $fiveNote): void {
            if (!$fiveNote->wasChanged(['is_completed', 'returned', 'is_supervisioned', 'is_archived', 'is_payed'])) {
                return;
            }

            $refresher = app(\App\Services\WorkReports\WorkReportCurrentStatusRefresher::class);

            $ids = $fiveNote->work_report_id
                ? [(int) $fiveNote->work_report_id]
                : \App\Models\WorkReport::query()
                    ->where('note_id', $fiveNote->note_id)
                    ->where('canceled', false)
                    ->pluck('id')
                    ->all();

            foreach ($ids as $id) {
                $refresher->refresh((int) $id);
            }
        });

        static::updating(function (FiveNote $fiveNote): void {
            // Uma D5 devolvida volta a ser uma nova pendência da empreiteira. Quando a
            // empreiteira a conclui novamente, o marcador de devolução anterior não pode
            // acompanhar a nova submissão, ou ela jamais alcançaria o retorno de D5.
            if ($fiveNote->isDirty('is_completed') && $fiveNote->is_completed) {
                $fiveNote->returned = false;
            }

            if ($fiveNote->isDirty(['note_id', 'work_report_id'])) {
                $fiveNote->d5_origin_key = $fiveNote->originKey();
            }
        });
    }

    private function originKey(): string
    {
        return $this->work_report_id
            ? 'work_report:' . $this->work_report_id
            : 'note:' . $this->note_id;
    }

    protected $casts = [
        'dispatch_at'      => 'datetime',
        'visible_partner'  => 'boolean',
        'is_completed'     => 'boolean',
        'completed_at'     => 'datetime',
        'is_supervisioned' => 'boolean',
        'supervisioned_at' => 'datetime',
        'is_payed'         => 'boolean',
        'payed_at'         => 'datetime',
        'is_archived'      => 'boolean',
        'isPassive'        => 'boolean',
        'returned'         => 'boolean',
    ];

    /**
     * D5 que a Medição/Pagamento ainda precisa criar/despachar: não arquivada, sem
     * retorno/fiscalização/pagamento, nunca liberada ao parceiro e sem produção de Pagamento.
     * D5 já despachada (mesmo sem fiscalização) NÃO entra aqui.
     */
    public function scopeAwaitingPaymentCreation($query)
    {
        return $query->where('is_archived', false)
            ->where('is_supervisioned', false)
            ->where('is_completed', false)
            ->where('returned', false)
            ->where('visible_partner', false)
            ->where('is_payed', false)
            ->whereNull('dispatch_at')
            ->whereDoesntHave('productions', fn ($p) => $p->whereHas('Service', fn ($s) => $s->where('service', 'Pagamento')));
    }

    public function isAwaitingPaymentCreation(): bool
    {
        return !$this->is_archived
            && !$this->is_supervisioned
            && !$this->is_completed
            && !$this->returned
            && !$this->visible_partner
            && !$this->is_payed
            && !$this->dispatch_at
            && !$this->productions()->whereHas('Service', fn ($s) => $s->where('service', 'Pagamento'))->exists();
    }

    /** D5 que ainda exige ação da Medição/Pagamento: criar/despachar ou liberar carta após fiscalização. */
    public function needsPaymentAction(): bool
    {
        return $this->isAwaitingPaymentCreation()
            || $this->isEligibleForPaymentReturn();
    }

    /**
     * Retorno de D5 (Ciclo 2) para Medição/Pagamento.
     *
     * Esta é propositalmente diferente da existência de uma D5 no Ciclo 1: uma D5
     * incompleta pode acompanhar o Ciclo 1, mas só entra no retorno depois de concluída
     * e aprovada pela Fiscalização. `returned` representa a devolução pendente à
     * empreiteira e, portanto, também invalida o retorno.
     */
    public function isEligibleForPaymentReturn(): bool
    {
        return $this->is_completed
            && $this->is_supervisioned
            && !$this->is_archived
            && !$this->returned;
    }

    /** Consulta equivalente a {@see isEligibleForPaymentReturn()}. */
    public function scopeEligibleForPaymentReturn($query)
    {
        return $query->where('is_completed', true)
            ->where('is_supervisioned', true)
            ->where('is_archived', false)
            ->where('returned', false);
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function WorkReport(): BelongsTo
    {
        return $this->belongsTo(WorkReport::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by_user_id')->withTrashed();
    }

    public function productions(): MorphToMany
    {
        return $this->morphToMany(
            Production::class,
            'productionable',
            'productionables',
            'productionable_id', // FK deste model (FiveNote) na pivot
            'production_id'      // FK do outro (Production) na pivot
        )->withTimestamps();
    }

    public function EvidenceFiles()
    {
        return $this->morphMany(EvidenceFile::class, 'evidenciable');
    }

    public function Comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class);
    }

    public function done(?string $responsible, ?string $comment = null)
    {
        $this->name                = $responsible ?? $this->name;
        $this->answered_by_user_id = auth()->id() ?: $this->answered_by_user_id;
        $this->is_completed        = true;
        $this->returned            = false;
        $this->completed_at        = now();
        $this->save();

        if ($comment) {
            $this->Comments()->create([
                'user_id' => auth()->id(),
                'message' => $comment,
            ]);
        }
    }

}
