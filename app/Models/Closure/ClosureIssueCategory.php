<?php

namespace App\Models\Closure;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

class ClosureIssueCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'active', 'display_order'];

    protected $casts = ['active' => 'boolean', 'display_order' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $category) {
            $category->slug = Str::slug((string) $category->name);

            if ($category->slug === '') {
                throw new RuntimeException('Informe um nome válido para a categoria de pendência.');
            }
        });

        static::updating(function (self $category) {
            if ($category->isDirty('slug')) {
                throw new RuntimeException('O identificador da categoria não pode ser alterado.');
            }
        });
    }

    public function Subcategories()
    {
        return $this->hasMany(ClosureIssueSubcategory::class);
    }

    public function Issues()
    {
        return $this->hasMany(ClosureIssue::class);
    }
}
