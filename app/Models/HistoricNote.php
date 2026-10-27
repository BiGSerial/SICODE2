<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'id_num',
        'num_stat',
        'dt_stats',
        'considerar_prazo',
        'pze',
        'status',
        'transicao_usuario',
    ];

    protected $casts = [
        'dt_stats' => 'datetime',
    ];

    public function Note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }
}
