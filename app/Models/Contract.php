<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id ',
        'number',
        'service',
        'construction',
        'date_end',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_contract_rules')
            ->withPivot(['posts', 'qtd', 'days', 'dispatch'])
            ->withTimestamps()
            ->orderBy('service');
    }
}
