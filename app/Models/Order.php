<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'ordem',
        'note_id',
        'descricao',
        'locInstalacao',
        'cenPlan',
        'prioridade',
        'statusSist',
        'statusUser',
        'cenTrab',
        'gpm',
        'custPlanejado',
        'custRealizado',
        'modifPor',
        'pep',
        'conjunto',
        'denConjunto',
        'dtEntrada',
        'service_cost',
        'canceled',
        'canceled_at',
        'canceled_by',
    ];

    protected $casts = [
        'canceled' => 'boolean',
        'canceled_at' => 'datetime',
        'service_cost' => 'decimal:2',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Operations()
    {
        return $this->hasMany(Operation::class);
    }

    public function Viabilities()
    {
        return $this->belongsToMany(Viability::class, 'order_viability');
    }

    public function WorkReports()
    {
        return $this->belongsToMany(WorkReport::class, 'order_work_report');
    }

    public function OperationResps()
    {
        return $this->hasMany(OperationResp::class);
    }

    public function Partials()
    {
        return $this->belongsToMany(Partial::class, 'order_partial');
    }

    public function CancellationRequests()
    {
        return $this->belongsToMany(CancellationRequest::class, 'cancellation_request_orders')->withTimestamps();
    }
}
