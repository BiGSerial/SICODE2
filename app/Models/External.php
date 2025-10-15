<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class External extends Model
{
    use HasFactory;



    protected $fillable = [
        'note_id',
        'user_id',
        'entity_id',
        'entidade',
        'status',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];

    public function Protocols()
    {
        return $this->hasMany(Protocol::class);
    }

    public function Comments()
    {
        return $this->hasMany(ExternalComment::class);
    }

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function Reclaims()
    {
        return $this->belongsToMany(Reclaim::class, 'external_reclaim')->withPivot('completed', 'completed_at');
    }

    public function Entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function Files()
    {
        return $this->morphToMany(File::class, 'fileable');
    }

    public function PoolPayments()
    {
        return $this->hasMany(ExternalPoolpayment::class);
    }

}
