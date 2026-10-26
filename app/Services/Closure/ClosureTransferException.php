<?php

namespace App\Services\Closure;

use RuntimeException;

/** Recusa de regra na transferência entre usuários; a mensagem é mostrada ao usuário. */
class ClosureTransferException extends RuntimeException
{
}
