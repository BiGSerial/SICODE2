<?php

namespace App\Models;

use App\Http\Livewire\Construction\Hiring\Actions\Hiring;
use App\Models\Edp_cipqa\TempAdsInfo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'note',
        'created_by',
        'dt_created',
        'dt_status',
        'user',
        'value',
        'currency',
        'eq_venda',
        'numPedido',
        'client',
        'group1',
        'group2',
        'group3',
        'group4',
        'group5',
        'pze',
        'num_material',
        'material',
        'nexp',
        'lexp',
        'pep',
        'nstats',
        'status',
        'days',
        'transaction',
        'validar_prazo',
        'rubrica',
        'pze_tratado',
        'days_stat',
        'pze_parecer',
        'days_left',
        'mmgd',
        'type_note',
        'centerjob',
        'doe',
        'postes',
        'mesalization',
        'txpriority',
        'updated_at',
        'created_at',
        'is45',
    ];



    protected $casts = [
        'dt_created' => 'datetime',
        'dt_status' => 'datetime',
        'mmgd' => 'boolean',
        'doe' => 'boolean',
        'is45' => 'boolean',

    ];

    public function Productions()
    {
        return $this->hasMany(Production::class);
    }

    public function latestProduction()
    {
        return $this->hasOne(Production::class)->latestOfMany('created_at');
    }

    public function Historic()
    {
        return $this->hasMany(Notetimeline::class);
    }

    public function Wpas()
    {
        return $this->hasMany(Wpa::class);
    }

    public function Priorities()
    {
        return $this->hasMany(Priority::class);
    }

    public function Orders()
    {
        return $this->hasMany(Order::class);
    }

    public function Files()
    {
        return $this->hasMany(File::class);
    }

    public function Viabilities()
    {
        // return $this->hasManyThrough(Viability::class, Order::class);
        return $this->hasMany(Viability::class);
    }

    public function Waitings()
    {
        return $this->hasMany(HiringWaiting::class);
    }

    public function Externals()
    {
        return $this->hasMany(External::class);
    }

    public function WorkForm()
    {
        return $this->hasOne(WorkReport::class);
    }

    public function d5Return()
    {
        return $this->hasOne(D5Return::class);
    }

    public function RamalForm()
    {
        return $this->hasOne(RamalReport::class);
    }

    public function Partials()
    {
        return $this->hasMany(Partial::class);
    }

    public function Approval()
    {
        return $this->hasOne(ViabilityApproval::class);
    }

    public function Adsform()
    {
        return $this->hasOne(Adsform::class);
    }

    public function OldAds()
    {
        return $this->hasMany(OldAdsInform::class);
    }


    public function Protests()
    {
        return $this->belongsToMany(Protest::class);
    }











    // Relação temporária
    public function TempAdsInfos()
    {
        return $this->hasMany(TempAdsInfo::class);
    }




    // Note Resources

    public function dueDate()
    {
        if ($this->is45) {
            return $this->dt_created->addDays(45);
        } elseif ($this->pze > 0) {
            return $this->dt_created->addDays($this->pze);
        } else {
            return false; // Default to 30 days if no specific PZE is set
        }
    }

    public function isLateDue()
    {
        $dueDate = $this->dueDate();
        if ($dueDate) {
            return now()->greaterThan($dueDate);
        }
        return false;
    }
}
