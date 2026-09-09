<?php

namespace App\Models\Files;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileDerivative extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'kind',
        'disk',
        'path',
        'mime',
        'size',
        'sha256',
        'width',
        'height',
    ];

    protected $casts = [
        'size'   => 'integer',
        'width'  => 'integer',
        'height' => 'integer',
    ];

    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
