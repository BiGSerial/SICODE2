<?php

namespace App\Models;

use App\Models\SicodeSql\Production as SicodeSqlProduction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Production extends Model
{
    use HasFactory;

    public const STATUS_IN_PROJECT_REVIEW = 30;
    public const STATUS_REJECTED_PROJECT_REVIEW = 31;
    public const STATUS_RELEASED_TO_FINISH = 32;

    protected $fillable = [
        'note_id',
        'service_id',
        'user_id',
        'company_id',
        'dispatch_by',
        'att_by',
        'dt_note',
        'status_note',
        'dispatch_at',
        'att_at',
        'completed_at',
        'partial_at',
        'confirmed_at',
        'stopped',
        'odi',
        'odd',
        'ods',
        'postes_u',
        'postes_l',
        'completed',
        'confirmed',
        'returned',
        'priority',
        'status',
        'block',
        'transferred',
        'tries',
        'mmgd',
        'conf_manual',
        'rejected',
        'manual',
        'dhstats',
        'postes_c',
        'eo',
        'iproject',
        'cadastro',
        'centroTrab',
        'block_wpa',
        'noinconsistency',
        'd5',
        'dfive', // Added for D5 integration
        'cad',
        'partial',
        'ma', //Meio Ambiente
        'supervision_by_partner_photos',
    ];

    protected $casts = [
        'manual'     => 'boolean',
        'mmgd'     => 'boolean',
        'dfive' => 'boolean',
        'd5'     => 'boolean',
        'cad'    => 'boolean',
        'partial' => 'boolean',
        'supervision_by_partner_photos' => 'boolean',
        'completed'   => 'boolean',
        'confirmed'   => 'boolean',
        'returned'    => 'boolean',
        'priority'    => 'boolean',
        'block'  => 'boolean',
        'dispatch_at' => 'datetime',
        'att_at'        => 'datetime',
        'completed_at'  => 'datetime',
        'partial_at'    => 'datetime',
        'confirmed_at'  => 'datetime',
        'dt_note'       => 'datetime',
        'dhstats' => 'datetime',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();

    }

    public function Dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatch_by', 'id')->withTrashed();
    }

    public function Att()
    {
        return $this->belongsTo(User::class, 'att_by', 'id')->withTrashed();
    }

    public function Company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function Analise()
    {
        return $this->hasOne(Analise::class);
    }

    public function Transfer()
    {
        return $this->hasMany(Prodtransfer::class);
    }

    public function LogProductions()
    {
        return $this->hasMany(SicodeSqlProduction::class);
    }

    public function Wpas()
    {
        return $this->hasMany(Wpa::class);
    }

    public function Priorities()
    {
        return $this->hasMany(Priority::class);
    }

    public function Files()
    {
        return $this->belongsToMany(File::class);
    }

    public function morphFiles(): MorphToMany
    {
        return $this->morphToMany(File::class, 'fileable')->withTimestamps();
    }

    public function Reclaim()
    {
        return $this->hasOne(Reclaim::class);
    }

    public function d5Return()
    {
        return $this->hasOne(D5Return::class);
    }

    public function Notetimelines()
    {
        return $this->hasMany(Notetimeline::class);
    }

    public function ProjectReviewCycles()
    {
        return $this->hasMany(ProjectReviewCycle::class)->orderBy('round_number');
    }

    public function ProjectReviewMessages()
    {
        return $this->hasMany(ProjectReviewMessage::class);
    }

    public function fiveNotes(): MorphToMany
    {
        return $this->morphedByMany(
            FiveNote::class,
            'productionable',   // usa productionable_type/_id
            'productionables',  // pivot
            'production_id',    // FK deste model na pivot
            'productionable_id' // FK do outro model na pivot
        )->withTimestamps();
    }


    // Redução para ultimo registro
    public function latestWpa()
    {
        return $this->hasOne(Wpa::class)->latest('id');
    }


    // Encerramento de Parcial
    public function partialFiscalDone(): ?\App\Models\Partial
    {
        $partial = $this->Note->Partials()
            ->orderBy('id', 'desc')
            ->where('allow', true)
            ->where('deny', false)
            ->where('complete', false)
            ->where('supervision', false)
            ->first();

        if ($partial) {
            $partial->supervision = true;
            $partial->supervision_at = now();
            $partial->supervision_id = auth()->user()->id;
            $partial->save();
        }

        return $partial;
    }

     public function partialPaymentDone(): ?\App\Models\Partial
    {
        $partial = $this->Note->Partials()
            ->orderBy('id', 'desc')
            ->where('allow', true)
            ->where('deny', false)
            ->where('complete', false)
            ->where('supervision', true)
            ->where('payment', false)
            ->first();

        if ($partial) {
            $partial->payment = true;
            $partial->complete = true;
            $partial->payment_at = now();
            $partial->payment_id = auth()->user()->id;
            $partial->save();
        } else {
            return null;
        }

        return $partial;
    }

     public function partialReject(string $motivo, bool $payment = false): ?\App\Models\Partial
    {
        $partial = $this->Note->Partials()
            ->orderBy('id', 'desc')
            ->where('allow', true)
            ->where('deny', false)
            ->where('complete', false)
            ->first();

        if ($partial) {


            $partial->payment = $payment;
            $partial->complete = false;
            $partial->payment_at = $payment ? now() : null;
            $partial->payment_id = $payment ? auth()->user()->id : null;
            $partial->engineer_info = $motivo;
            $partial->complete = false;
            $partial->supervision = !$payment ? true : false;
            $partial->supervision_at = !$payment ? now() : null;
            $partial->supervision_id = !$payment ? auth()->user()->id : null;
            $partial->allow = false;
            $partial->deny = true;
            $partial->save();

        } else {
            return null;
        }

        return $partial;
    }

}
