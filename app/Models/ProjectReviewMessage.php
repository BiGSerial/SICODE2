<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectReviewMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'cycle_id',
        'user_id',
        'parent_id',
        'message',
    ];

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function Cycle()
    {
        return $this->belongsTo(ProjectReviewCycle::class, 'cycle_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function Parent()
    {
        return $this->belongsTo(ProjectReviewMessage::class, 'parent_id');
    }
}
