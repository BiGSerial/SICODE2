<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Quem é N1 ou N2 de qual empresa (definido pela Gestão em Qualidade > Equipe). */
class QualityMember extends Model
{
    public const N1 = 'N1';

    public const N2 = 'N2';

    protected $fillable = ['user_id', 'company_id', 'role', 'active', 'created_by'];

    protected $casts = ['active' => 'boolean'];

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function Company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }
}
