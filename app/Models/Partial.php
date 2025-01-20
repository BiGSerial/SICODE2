<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partial extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'company_id',
        'user_id',
        'observation',
        'engineer_info',
        'allow',
        'deny',
        'payment',
        'supervision',
        'engineer_id',
        'supervision_id',
        'payment_id',
        'decision_at',
        'payment_at',
        'supervision_at',
        'complete',
        'responsible'
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Order()
    {
        return $this->belongsToMany(Order::class, 'order_partial');
    }

    public function Company()
    {
        return $this->belongsTo(Company::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Engineer()
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    public function Supervision()
    {
        return $this->belongsTo(User::class, 'supervision_id');
    }

    public function Payment()
    {
        return $this->belongsTo(User::class, 'payment_id');
    }

    public function Orders()
    {
        return $this->belongsToMany(Order::class, 'order_partial');
    }

    public function Files()
    {
        return $this->belongsToMany(File::class, 'file_partial');
    }


}
