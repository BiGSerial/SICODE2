<?php

namespace App\Exports\Reports;

use App\Exports\ProjectReview\Sheets\StyledArraySheetExport;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

/** Aba padrão SICODE que preserva o valor 0 (sem a comparação frouxa que transforma 0 em célula vazia). */
class PostWorkProcessStrictSheet extends StyledArraySheetExport implements WithStrictNullComparison
{
}
