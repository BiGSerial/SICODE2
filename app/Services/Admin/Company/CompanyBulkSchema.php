<?php

namespace App\Services\Admin\Company;

use Illuminate\Support\Str;

/**
 * Contrato único da planilha em massa de empresas: usado pela exportação
 * (que gera o arquivo) e pela importação (que valida o que voltou).
 */
class CompanyBulkSchema
{
    public const MAX_ROWS = 2000;

    public const FIRST_ROW = 2;

    public const COMPANIES = 'Empresas';

    public const CENTERS = 'Centros e Depositos';

    public const CONTRACTS = 'Contratos';

    public const ACTIVITIES = 'Atividades';

    public const LISTS = 'Listas';

    public const ACTION_SAVE = 'salvar';

    public const ACTION_REMOVE = 'remover';

    public const ACTION_KEEP = 'manter';

    public const CONTRACT_TYPES = ['Serviço', 'Obra', 'Serviço e Obra'];

    public const UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    /** @return array<string, string[]> cabeçalhos por aba (a ordem das colunas é fixa) */
    public static function headers(): array
    {
        return [
            self::COMPANIES  => ['Ação', 'Empresa *', 'Unidade de (matriz)', 'E-mail *', 'Telefone', 'Endereço', 'Complemento', 'Município', 'UF', 'Situação', 'Chave'],
            self::CENTERS    => ['Ação', 'Empresa / Unidade *', 'Código *', 'Centro', 'Depósito'],
            self::CONTRACTS  => ['Ação', 'Empresa / Unidade *', 'Contrato *', 'Validade *', 'Tipo *', 'Chave'],
            self::ACTIVITIES => ['Ação', 'Contrato *', 'Atividade *', 'Despachar'],
        ];
    }

    public static function sheetNames(): array
    {
        return array_keys(self::headers());
    }

    /** Normaliza texto para comparação (sem acento, caixa ou pontuação). */
    public static function norm(?string $value): string
    {
        return Str::of(Str::ascii((string) $value))->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();
    }

    public static function actionLabel(string $key): string
    {
        return match ($key) {
            self::ACTION_REMOVE => 'Remover',
            self::ACTION_KEEP   => 'Manter',
            default             => 'Salvar',
        };
    }

    public static function displayName(?string $parentName, string $name): string
    {
        return $parentName ? "{$parentName} / {$name}" : $name;
    }
}
