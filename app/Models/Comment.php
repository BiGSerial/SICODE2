<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'message', 'restrict'];



    public static function boot()
    {
        parent::boot();

        // Remove Register from search to Contracteds Users.
        static::addGlobalScope('restrict', function ($query) {

            if (auth()->check() && auth()->user()->contract) {

                $query->where('restrict', false);
            }
        });
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }


    public function Viabilities()
    {
        return $this->belongsToMany(Viability::class);
    }
}
