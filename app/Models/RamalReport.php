<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RamalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'company_id',
        'user_id',
        'date',
        'equipment',
        'connection',
        'observation',
        'retry',
        'rejected',
        'rejected_at',
        'informed_at',
    ];

    protected $casts = [

         'rejected_at' => 'datetime',
         'informed_at' => 'datetime',
     ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Company()
    {
        return $this->belongsTo(Company::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Orders()
    {
        return $this->belongsToMany(Order::class, 'order_ramal_report');
    }

    public function BtzeroEquipment()
    {
        return $this->hasMany(BtzeroEquipment::class)->orderBy('type')->orderBy('patrimony');

    }

    public function ReturnRamal()
    {
        return $this->hasMany(ReturnRamal::class);
    }
}
