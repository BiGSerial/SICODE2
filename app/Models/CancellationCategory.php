<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

class CancellationCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'active',
        'require_evidence',
        'min_evidence_files',
        'display_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'require_evidence' => 'boolean',
        'min_evidence_files' => 'integer',
        'display_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $category) {
            $slug = Str::slug((string) $category->name);
            if ($slug === '') {
                throw new RuntimeException('Não foi possível gerar slug para a categoria.');
            }
            $category->slug = $slug;
        });

        static::updating(function (self $category) {
            if ($category->isDirty('slug')) {
                throw new RuntimeException('Slug da categoria não pode ser alterado após criação.');
            }
        });
    }

    public function requests()
    {
        return $this->hasMany(CancellationRequest::class, 'category_id');
    }
}
