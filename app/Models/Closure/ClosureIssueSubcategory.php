<?php

namespace App\Models\Closure;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

class ClosureIssueSubcategory extends Model
{
    protected $fillable = ['closure_issue_category_id', 'name', 'slug', 'description', 'active', 'requires_description', 'display_order'];

    protected $casts = ['active' => 'boolean', 'requires_description' => 'boolean', 'display_order' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $subcategory) {
            $subcategory->slug = Str::slug((string) $subcategory->name);

            if ($subcategory->slug === '') {
                throw new RuntimeException('Informe um nome válido para a subcategoria de pendência.');
            }
        });

        static::updating(function (self $subcategory) {
            if ($subcategory->isDirty('slug')) {
                throw new RuntimeException('O identificador da subcategoria não pode ser alterado.');
            }
        });
    }

    public function Category()
    {
        return $this->belongsTo(ClosureIssueCategory::class, 'closure_issue_category_id');
    }

    public function Issues()
    {
        return $this->hasMany(ClosureIssue::class);
    }
}
