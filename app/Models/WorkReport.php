<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'dd',
        'informer',
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
        return $this->hasMany(Equipment::class);
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
}
