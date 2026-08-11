<?php

namespace App\Services\Admin\Company;

use App\Imports\Admin\Company\UserRegistrationWorkbookImport;
use App\Models\Company;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class UserRegistrationWorkbookService
{
    public function validate(Company $company, string $path, string $disk = 'local'): array
    {
        $root = $this->rootCompany($company);
        $units = $this->unitsFor($root);
        $contracts = $this->contractsFor($units);

        $import = new UserRegistrationWorkbookImport();
        Excel::import($import, $path, $disk);

        $unitResult = $this->validateUnits($import->units, $root, $units);
        $userResult = $this->validateUsers($import->users, $root, $units, $contracts);

        return [
            'root_id' => $root->id,
            'valid_units' => $unitResult['valid'],
            'invalid_units' => $unitResult['invalid'],
            'valid_users' => $userResult['valid'],
            'invalid_users' => $userResult['invalid'],
            'summary' => [
                'units_valid' => count($unitResult['valid']),
                'units_invalid' => count($unitResult['invalid']),
                'users_valid' => count($userResult['valid']),
                'users_invalid' => count($userResult['invalid']),
                'users_create' => collect($userResult['valid'])->where('action', 'Criar')->count(),
                'users_keep' => collect($userResult['valid'])->where('action', 'Manter')->count(),
                'users_remove' => collect($userResult['valid'])->where('action', 'Remover')->count(),
            ],
        ];
    }

    public function processValid(Company $company, array $validation): array
    {
        $root = $this->rootCompany($company);
        $createdUnits = 0;
        $createdUsers = 0;
        $updatedUsers = 0;
        $removedUsers = 0;

        foreach ($validation['valid_units'] ?? [] as $unitRow) {
            if ($unitRow['action'] !== 'Criar') {
                continue;
            }

            $unit = Company::firstOrCreate(
                [
                    'parent_id' => $root->id,
                    'name' => $unitRow['unit_name'],
                ],
                [
                    'email' => $unitRow['email'],
                    'telephone' => $unitRow['telephone'],
                ]
            );
            if ($unit->wasRecentlyCreated) {
                $createdUnits++;
            }
        }

        $root->refresh()->load('branches');
        $units = $this->unitsFor($root);
        $contracts = $this->contractsFor($units);

        foreach ($validation['valid_users'] ?? [] as $userRow) {
            $unit = $this->findUnitByDisplayName($units, $userRow['unit']);
            $contract = $this->findContractByLabel($contracts, $userRow['contract']);

            if ($userRow['action'] === 'Remover') {
                $user = User::withTrashed()->where('email', $userRow['email'])->first();
                if ($user && !$user->trashed()) {
                    $user->delete();
                    $removedUsers++;
                }
                continue;
            }

            $payload = [
                'name' => $userRow['name'],
                'Registration' => $userRow['registration'],
                'company_id' => $unit?->id,
                'admin' => $userRow['admin'],
                'operator' => $userRow['operator'],
                'user' => $userRow['user'],
                'management' => $userRow['management'],
            ];

            $user = User::withTrashed()->where('email', $userRow['email'])->first();
            if (!$user) {
                $user = User::create(array_merge($payload, [
                    'email' => $userRow['email'],
                    'password' => Hash::make('123456'),
                    'first_pass' => true,
                ]));
                $createdUsers++;
            } else {
                if ($user->trashed()) {
                    $user->restore();
                }
                $user->update($payload);
                $updatedUsers++;
            }

            if ($contract) {
                $primaryServiceId = $contract->services()->first()?->uuid;
                if ($primaryServiceId) {
                    $user->Employee()->updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'contract_id' => $contract->id,
                            'service_id' => $primaryServiceId,
                        ]
                    );
                }

                foreach ($contract->services as $service) {
                    $user->ToServices()->updateOrCreate(
                        ['service_id' => $service->uuid],
                        [
                            'service' => true,
                            'dispatch' => (bool) $service->pivot->dispatch,
                        ]
                    );
                }
            }
        }

        return compact('createdUnits', 'createdUsers', 'updatedUsers', 'removedUsers');
    }

    private function validateUnits(array $rows, Company $root, Collection $units): array
    {
        $valid = [];
        $invalid = [];

        foreach ($this->dataRows($rows, 'Acao') as $index => $row) {
            $action = $this->cell($row, 0);
            $unitName = $this->cell($row, 2);

            if (!$action && !$unitName) {
                continue;
            }
            if ($action === 'Criar' && !$unitName && !$this->cell($row, 3) && !$this->cell($row, 4)) {
                continue;
            }

            $errors = [];
            if (!in_array($action, ['Manter', 'Criar', 'Atualizar'], true)) {
                $errors[] = 'Acao invalida.';
            }
            if ($action === 'Criar' && !$unitName) {
                $errors[] = 'Informe o nome da unidade.';
            }
            if ($action === 'Criar' && $units->contains(fn ($unit) => Str::lower($unit->name) === Str::lower($unitName))) {
                $errors[] = 'Unidade ja cadastrada nesta concentradora.';
            }

            $payload = [
                'line' => $index + 1,
                'action' => $action,
                'unit_name' => $unitName,
                'email' => $this->cell($row, 3),
                'telephone' => $this->cell($row, 4),
                'error' => implode(' ', $errors),
            ];

            $errors ? $invalid[] = $payload : $valid[] = $payload;
        }

        return compact('valid', 'invalid');
    }

    private function validateUsers(array $rows, Company $root, Collection $units, Collection $contracts): array
    {
        $valid = [];
        $invalid = [];
        $seenEmails = [];

        foreach ($this->dataRows($rows, 'Acao') as $index => $row) {
            $action = $this->cell($row, 0);
            $name = $this->cell($row, 1);
            $email = Str::lower($this->cell($row, 2));

            if (!$action && !$name && !$email) {
                continue;
            }
            if ($action === 'Criar' && !$name && !$email && !$this->cell($row, 3)) {
                continue;
            }

            $unitLabel = $this->cell($row, 4);
            $contractLabel = $this->cell($row, 5);
            $existingUser = $email ? User::withTrashed()->where('email', $email)->first() : null;
            $unit = $this->findUnitByDisplayName($units, $unitLabel);
            $contract = $this->findContractByLabel($contracts, $contractLabel);
            $errors = [];

            if (!in_array($action, ['Criar', 'Manter', 'Remover'], true)) {
                $errors[] = 'Acao invalida.';
            }
            if (!$name && $action !== 'Remover') {
                $errors[] = 'Nome obrigatorio.';
            }
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email invalido.';
            }
            if ($email && in_array($email, $seenEmails, true)) {
                $errors[] = 'Email duplicado na ficha.';
            }
            if ($email) {
                $seenEmails[] = $email;
            }
            if ($action === 'Criar' && $existingUser) {
                $errors[] = 'Usuario marcado como Criar ja existe.';
            }
            if (in_array($action, ['Manter', 'Remover'], true) && !$existingUser) {
                $errors[] = 'Usuario marcado como Manter/Remover nao existe.';
            }
            if ($existingUser && !$this->userBelongsToScope($existingUser, $units)) {
                $errors[] = 'Usuario pertence a outra empresa/concentradora.';
            }
            if (!$unit) {
                $errors[] = 'Empresa/Unidade invalida para esta concentradora.';
            }
            if ($contractLabel && !$contract) {
                $errors[] = 'Contrato invalido para esta concentradora/unidade.';
            }
            if ($unit && !$contractLabel && $this->contractsFor(collect([$unit, $unit->parent])->filter())->count() > 1) {
                $errors[] = 'Contrato obrigatorio para unidade com mais de um contrato aplicavel.';
            }
            foreach (['admin' => 6, 'operator' => 7, 'user' => 8, 'management' => 9] as $field => $position) {
                if (!in_array($this->cell($row, $position), ['Sim', 'Nao'], true)) {
                    $errors[] = "Valor invalido em {$field}.";
                }
            }

            $payload = [
                'line' => $index + 1,
                'action' => $action,
                'name' => $name,
                'email' => $email,
                'registration' => $this->cell($row, 3),
                'unit' => $unitLabel,
                'contract' => $contractLabel,
                'admin' => $this->cell($row, 6) === 'Sim',
                'operator' => $this->cell($row, 7) === 'Sim',
                'user' => $this->cell($row, 8) === 'Sim',
                'management' => $this->cell($row, 9) === 'Sim',
                'error' => implode(' ', $errors),
            ];

            $errors ? $invalid[] = $payload : $valid[] = $payload;
        }

        return compact('valid', 'invalid');
    }

    private function rootCompany(Company $company): Company
    {
        return ($company->parent ?: $company)->load('branches', 'contracts.services');
    }

    private function unitsFor(Company $root): Collection
    {
        return collect([$root])
            ->merge($root->branches()->with('parent', 'contracts.services')->get())
            ->unique('id')
            ->values();
    }

    private function contractsFor(Collection $units): Collection
    {
        return $units
            ->filter()
            ->flatMap(fn ($unit) => $unit->contracts()->with('company.parent', 'services')->get())
            ->unique('id')
            ->values();
    }

    private function userBelongsToScope(User $user, Collection $units): bool
    {
        $unitIds = $units->pluck('id')->all();

        return in_array($user->company_id, $unitIds, true)
            || in_array($user->Employee?->Contract?->company_id, $unitIds, true);
    }

    private function findUnitByDisplayName(Collection $units, ?string $label): ?Company
    {
        return $units->first(fn ($unit) => $unit->display_name === $label || $unit->name === $label);
    }

    private function findContractByLabel(Collection $contracts, ?string $label): ?Contract
    {
        if (!$label) {
            return null;
        }

        return $contracts->first(fn ($contract) => "{$contract->number} | {$contract->company?->display_name}" === $label);
    }

    private function dataRows(array $rows, string $firstHeader): array
    {
        foreach ($rows as $index => $row) {
            if ($this->cell($row, 0) === $firstHeader) {
                return array_slice($rows, $index + 1, null, true);
            }
        }

        return [];
    }

    private function cell(array $row, int $index): string
    {
        return trim((string) ($row[$index] ?? ''));
    }
}
