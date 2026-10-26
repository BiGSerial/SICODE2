<?php

namespace App\Services\Closure;

/**
 * De qual lado do Encerramento o usuário veio ao abrir Gestão da Obra / Detalhe da Ordem: `dispatch`
 * (Despacho/Controle) ou `service` (Minha atividade). Decide o menu lateral, o breadcrumb e o "voltar";
 * sem isso, quem tem as duas permissões sempre cairia no menu de Despacho. Viaja na URL como `?ctx=`.
 */
class ClosureNavContext
{
    public const DISPATCH = 'dispatch';

    public const SERVICE = 'service';

    public static function resolve(?string $requested): string
    {
        $user = auth()->user();

        if ($requested === self::SERVICE && $user?->can('closure.work')) {
            return self::SERVICE;
        }

        if ($requested === self::DISPATCH && $user?->can('closure.dispatch')) {
            return self::DISPATCH;
        }

        return $user?->can('closure.dispatch') ? self::DISPATCH : self::SERVICE;
    }

    /** Lista inicial do contexto (destino do breadcrumb "Encerramento" e do botão voltar). */
    public static function homeUrl(string $ctx): string
    {
        return ClosureServiceLocator::url($ctx === self::SERVICE ? 'services.main' : 'dispatch.main') ?? route('home');
    }
}
