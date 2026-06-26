<?php

namespace App\Models\SicodeSql\Legal;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LegalCaseSummary extends Model
{
    protected $connection = 'sqlsrv2';

    protected $table = 'tbl_Resumo_Juridico';

    protected $primaryKey = 'ID';

    public $incrementing = true;

    public $timestamps = false;

    public function save(array $options = []): bool
    {
        throw new \LogicException('LegalCaseSummary is read-only.');
    }

    public function delete()
    {
        throw new \LogicException('LegalCaseSummary is read-only.');
    }

    public function scopeForProcess(Builder $query, string $process): Builder
    {
        $digits = preg_replace('/\D+/', '', $process) ?: trim($process);

        return $query->where('PROCESSO', $digits);
    }
}
