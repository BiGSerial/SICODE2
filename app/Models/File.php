<?php

namespace App\Models;

use App\Models\Files\FileDerivative;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'user_id',
        'service_id',
        'file_name',
        'path',
        'disk',
        'ext',
        'mime',
        'size',
        'sha256',
        'noexists',
        'original_name',
        'suspicious',
    ];

    protected $casts = [
        'noexists'   => 'boolean',
        'suspicious' => 'boolean',
        'size'       => 'integer',
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

    public function derivatives()
    {
        return $this->hasMany(FileDerivative::class);
    }

    public function thumbnail()
    {
        return $this->hasOne(FileDerivative::class)->where('kind', 'thumbnail');
    }

    public function isTacitAdsRestricted(): bool
    {
        return $this->Adsforms()
            ->where('tacit', true)
            ->whereNotNull('work_report_id')
            ->exists();
    }

    public function getExtensionAttribute(): string
    {
        return (string) ($this->ext ?: pathinfo((string) $this->file_name, PATHINFO_EXTENSION));
    }

    public function getStoredNameAttribute(): string
    {
        $name      = (string) ($this->original_name ?: $this->file_name ?: 'arquivo');
        $extension = $this->extension;

        if ($extension !== '' && !str_ends_with(strtolower($name), '.' . strtolower($extension))) {
            return $name . '.' . $extension;
        }

        return $name;
    }

    public function getSizeAttribute(): int
    {
        $storedSize = $this->attributes['size'] ?? null;

        if ($storedSize !== null) {
            return (int) $storedSize;
        }

        $disk = (string) ($this->disk ?: 'local');

        return $this->path && Storage::disk($disk)->exists($this->path)
            ? (int) Storage::disk($disk)->size($this->path)
            : 0;
    }
}
