<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HiringWaiting extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'user_id',
        'reclaim_id',
        'category',
        'complete',
    ];

    protected $casts = [
        'complete' => 'boolean',
    ];

    public function scopeFilter($query, array $filters)
    {
        return $query->where('complete', false)
            ->when(data_get($filters, 'cjobes'), function ($q) use ($filters) {
                $q->whereHas('Note.Orders.Operations', function ($sub) use ($filters) {
                    $sub->where('cenTrab', $filters['cjobes'])
                        ->where('operacao', '0010');
                });
            })
            ->when(data_get($filters, 'typeNote'), function ($q) use ($filters) {
                $q->whereHas('Note', function ($sub) use ($filters) {
                    $sub->where('type_note', $filters['typeNote']);
                });
            })
            ->when(data_get($filters, 'search'), function ($q) use ($filters) {
                $q->whereHas('Note', function ($sub) use ($filters) {
                    $sub->where('note', 'like', '%'.$filters['search'].'%')
                        ->orWhereRelation('Orders', 'ordem', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when(($multi = collect(data_get($filters, 'multiSearch', []))->filter()->values())->count(), function ($q) use ($multi) {
                $q->whereHas('Note', function ($sub) use ($multi) {
                    $sub->whereIn('note', $multi)
                        ->orWhereRelation('Orders', function ($qq) use ($multi) {
                            $qq->whereIn('ordem', $multi);
                        });
                });
            });
    }

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function Reclaim()
    {
        return $this->belongsTo(Reclaim::class);
    }


}
