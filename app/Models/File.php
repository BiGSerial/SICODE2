<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'user_id',
        'service_id',
        'file_name',
        'path',
        'ext',
        'noexists',
        'original_name',
        'suspicious',
    ];

    protected $casts = [
        'noexists' => 'boolean',
        'suspicious' => 'boolean',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function Forms()
    {
        return $this->belongsToMany(Form::class);
    }

    public function Productions()
    {
        return $this->belongsToMany(Production::class);
    }

    public function MorphProductions()
    {
        return $this->morphedByMany(Production::class, 'fileable')->withTimestamps();
    }

    public function Viabilities()
    {
        return $this->belongsToMany(Viability::class);
    }

    public function Parcials()
    {
        return $this->belongsToMany(Partial::class, 'file_partial');
    }

    public function Adsforms()
    {
        return $this->belongsToMany(Adsform::class, 'adsforms_files');
    }

    public function Externals()
    {
        return $this->morphedByMany(External::class, 'fileable');
    }

    public function WorkReports()
    {
        return $this->morphedByMany(WorkReport::class, 'fileable')->withTimestamps();
    }

    public function Reclaims()
    {
        return $this->morphedByMany(Reclaim::class, 'fileable')->withTimestamps();
    }

    public function isTacitAdsRestricted(): bool
    {
        return $this->Adsforms()
            ->where('tacit', true)
            ->whereNotNull('work_report_id')
            ->exists();
    }
}
