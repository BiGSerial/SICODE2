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
        'engineer_at'
    ];

    public function Order()
    {
        return $this->belongsTo(Order::class);
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

    public function Form()
    {
        return $this->hasOne(Form::class);
    }

    public function Comments()
    {
        return $this->belongsToMany(Comment::class);
    }
}
