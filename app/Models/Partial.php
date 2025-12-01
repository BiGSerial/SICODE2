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
        'responsible',
        'value'
    ];


    protected $casts = [
        'decision_at' => 'datetime',
        'payment_at' => 'datetime',
        'supervision_at' => 'datetime',
        'complete' => 'boolean',
        'allow' => 'boolean',
        'deny' => 'boolean',
        'payment' => 'boolean',
        'supervision' => 'boolean',
        'value' => 'decimal:2',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    // public function Order()
    // {
    //     return $this->belongsToMany(Order::class, 'order_partial');
    // }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function engineer()
    {
        return $this->belongsTo(User::class, 'engineer_id', 'id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervision_id', 'id');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payment_id', 'id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_partial');
    }

    public function files()
    {
        return $this->belongsToMany(File::class, 'file_partial');
    }




}
