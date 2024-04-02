<?php

namespace App\Models;

use App\Models\SicodeSql\Production as SicodeSqlProduction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    use HasFactory;

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
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatch_by', 'id');
    }

    public function Att()
    {
        return $this->belongsTo(User::class, 'att_by', 'id');
    }

    public function Company()
    {
        return $this->belongsTo(Company::class);
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
}
