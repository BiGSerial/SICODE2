<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnWork extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_report_id',
        'service_id',
        'user_id',
        'category',
        'text_obs',
    ];

    public function Workreport()
    {
        return $this->belongsTo(WorkReport::class, 'work_report_id', 'id');
    }

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
