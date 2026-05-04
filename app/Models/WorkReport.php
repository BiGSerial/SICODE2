<?php

namespace App\Models;

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

    public function Files()
    {
        return $this->morphToMany(File::class, 'fileable')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('canceled', false);
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
}
