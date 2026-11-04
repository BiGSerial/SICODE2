<?php

namespace App\Support\Catalog;

use RuntimeException;

/** Erro de regra do cadastro (nome repetido, item em uso…): a mensagem já vai em português para o aviso da tela. */
class CatalogException extends RuntimeException
{
}
