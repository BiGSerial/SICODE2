<?php

namespace App\Exports\Admin\Company;

use App\Exports\Admin\Company\Sheets\UserRegistrationErrorSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserRegistrationErrorsExport implements WithMultipleSheets
{
    public function __construct(private readonly array $validation)
    {
    }

    public function sheets(): array
    {
        return [
            new UserRegistrationErrorSheet('Erros Usuarios', [
                'Linha',
                'Acao',
                'Nome',
                'Email',
                'Empresa/Unidade',
                'Contrato',
                'Erro',
                'Como corrigir',
            ], collect($this->validation['invalid_users'] ?? [])->map(fn ($row) => [
                $row['line'] ?? '',
                $row['action'] ?? '',
                $row['name'] ?? '',
                $row['email'] ?? '',
                $row['unit'] ?? '',
                $row['contract'] ?? '',
                $row['error'] ?? '',
                'Corrija a linha na ficha original ou ajuste o cadastro no SICODE antes de importar.',
            ])->all()),
            new UserRegistrationErrorSheet('Erros Filiais', [
                'Linha',
                'Acao',
                'Unidade',
                'Email',
                'Telefone',
                'Erro',
                'Como corrigir',
            ], collect($this->validation['invalid_units'] ?? [])->map(fn ($row) => [
                $row['line'] ?? '',
                $row['action'] ?? '',
                $row['unit_name'] ?? '',
                $row['email'] ?? '',
                $row['telephone'] ?? '',
                $row['error'] ?? '',
                'Corrija a unidade na ficha original ou associe a empresa existente antes de importar.',
            ])->all()),
        ];
    }
}
