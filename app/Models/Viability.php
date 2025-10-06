<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Viability extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'company_id',
        'user_id',
        'engineer_id',
        'init_at',
        'sended_at',
        'returned_at',
        'tacit_at',
        'completed_at',
        'tacit',
        'completed',
        'canceled',
        'rejected',
        'approved',
        'engineer',
        'engineer_at',
        'hired',
        'hired_at',
        'status',
        'replica',
        'treplica',
        'inActivity',
        'note_id',
        'visible_partner',
        'rehired',
        'value',
    ];

    protected $casts = [
        'init_at' => 'datetime',
        'sended_at' => 'datetime',
        'returned_at' => 'datetime',
        'tacit_at' => 'datetime',
        'completed_at' => 'datetime',
        'engineer_at' => 'datetime',
        'hired_at' => 'datetime',
        'tacit' => 'boolean',
        'completed' => 'boolean',
        'canceled' => 'boolean',
        'rejected' => 'boolean',
        'approved' => 'boolean',
        'engineer' => 'boolean',
        'hired' => 'boolean',
        'replica' => 'boolean',
        'treplica' => 'boolean',
        'inActivity' => 'boolean',
        'visible_partner' => 'boolean',
        'rehired' => 'boolean',
        'status' => 'integer',
    ];

    public function Order()
    {
        return $this->belongsTo(Order::class);
    }

    public function Company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function Engineer()
    {
        return $this->belongsTo(User::class, 'engineer_id')->withTrashed();
    }

    public function Form()
    {
        return $this->hasOne(Form::class);
    }

    public function Comments()
    {
        return $this->belongsToMany(Comment::class);
    }

    public function Reclaims()
    {
        return $this->belongsToMany(Reclaim::class);
    }

    public function Files()
    {
        return $this->belongsToMany(File::class);
    }

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Justification()
    {
        return $this->hasOne(TacitComment::class);
    }

    public function Orders()
    {
        return $this->belongsToMany(Order::class, 'order_viability');
    }

    public function rehiringAudits()
    {
        return $this->hasMany(ViabilityRehiringAudit::class);
    }



    // Prazo de Viabilidade
    public function Days()
    {
        return $this->hasMany(Daysviab::class);
    }

    public function getDays(): int
    {
        return $this->Days()->sum('days');
    }

    public function addDays(int $limit, int $days, string $reason = null)
    {
        if ($this->getDays() + $days > $limit) {
            $days = $limit - $this->getDays();

            if ($days <= 0) {
                return;
            }

        } elseif ($this->getDays() + $days < 0) {
            $days = $this->getDays() * -1;
        }

        return $this->Days()->Create([
            'days' => $days,
            'user_id' => auth()->user()->id,
            'reason' => $reason
            ]);
    }
}
