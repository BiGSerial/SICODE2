<?php

namespace App\Http\Controllers;

use App\Exports\Partner\PartnerUserImportTemplateExport;
use App\Imports\PartnerUserBulkImport;
use App\Models\Andresscompany;
use App\Models\Company;
use App\Models\Contract;
use App\Models\PartnerAdminAuditEvent;
use App\Models\PartnerUserBranch;
use App\Models\PartnerUserPermissionException;
use App\Models\User;
use App\Services\PartnerAccess\PartnerAccessGate;
use App\Services\PartnerAccess\PartnerPermissionCatalog;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PartnerAdminController extends Controller
{
    public function users(Request $request): ViewContract
    {
        $permissionCompanyId = $this->permissionCompanyId($request);
        $companyIds = $this->branchCompanyIdsFor($permissionCompanyId);
        $status = $request->query('status') === 'disabled' ? 'disabled' : 'active';

        $users = User::query()
            ->with([
                'Company:id,parent_id,name',
                'partnerBranchAddresses' => fn ($query) => $query
                    ->wherePivot('company_id', $permissionCompanyId)
                    ->with('Company:id,parent_id,name')
                    ->orderBy('city')
                    ->orderBy('street'),
            ])
            ->where('onlyparner', true)
            ->whereIn('company_id', $companyIds)
            ->when($status === 'disabled', fn ($query) => $query->onlyTrashed())
            ->when($status === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('name')
            ->paginate(25);

        $activeCount = User::query()
            ->where('onlyparner', true)
            ->whereIn('company_id', $companyIds)
            ->whereNull('deleted_at')
            ->count();
        $disabledCount = User::onlyTrashed()
            ->where('onlyparner', true)
            ->whereIn('company_id', $companyIds)
            ->count();

        return view('partner.admin.users', [
            'users' => $users,
            'status' => $status,
            'managedCompany' => Company::withTrashed()->find($permissionCompanyId),
            'branchCount' => max(count($companyIds) - 1, 0),
            'userCount' => $activeCount + $disabledCount,
            'activeCount' => $activeCount,
            'disabledCount' => $disabledCount,
        ]);
    }

    public function createUser(Request $request): ViewContract
    {
        $permissionCompanyId = $this->permissionCompanyId($request);

        return view('partner.admin.user-form', [
            'user' => new User(),
            'managedCompany' => Company::withTrashed()->find($permissionCompanyId),
            'branches' => $this->branchesFor($permissionCompanyId),
            'selectedBranches' => collect(),
            'userPermissionCatalog' => [],
            'userPermissionValues' => [],
            'canManageUserPermissions' => false,
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.create'), 403);

        $companyId = $this->companyId($request);
        $permissionCompanyId = $this->permissionCompanyId($request);
        $data = $this->validateUserData($request);
        $branchIds = $this->validBranchIds($request, $permissionCompanyId);

        $user = DB::transaction(function () use ($data, $branchIds, $companyId, $permissionCompanyId, $request) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(123456),
                'first_pass' => true,
                'onlyparner' => true,
                'user' => true,
                'company_id' => $companyId,
            ]);

            $user->Companies()->syncWithoutDetaching([$companyId]);
            $this->syncBranches($user, $permissionCompanyId, $branchIds, $request->user()->id);
            $this->audit($request, PartnerAdminAuditEvent::CREATED_USER, $user, [
                'branches' => $branchIds,
            ]);

            return $user;
        });

        return redirect()
            ->route('partner.admin.users.edit', $user)
            ->with('status', 'Usuário criado com senha temporária 123456.');
    }

    public function editUser(Request $request, User $user): ViewContract
    {
        $companyId = $this->companyId($request);
        $permissionCompanyId = $this->permissionCompanyId($request);
        $target = $this->targetUser($user, $companyId);

        return view('partner.admin.user-form', [
            'user' => $target,
            'managedCompany' => Company::withTrashed()->find($permissionCompanyId),
            'branches' => $this->branchesFor($permissionCompanyId),
            'selectedBranches' => $target->partnerBranches()
                ->where('company_id', $permissionCompanyId)
                ->pluck('branch_id'),
            'userPermissionCatalog' => $this->editablePermissionCatalog($permissionCompanyId),
            'userPermissionValues' => $this->userPermissionValues($target, $permissionCompanyId),
            'canManageUserPermissions' => PartnerAccessGate::allows($request->user(), 'admin_user_exceptions.manage'),
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.update'), 403);

        $companyId = $this->companyId($request);
        $permissionCompanyId = $this->permissionCompanyId($request);
        $target = $this->targetUser($user, $companyId);
        $data = $this->validateUserData($request, $target);
        $branchIds = $this->validBranchIds($request, $permissionCompanyId);

        DB::transaction(function () use ($target, $data, $branchIds, $companyId, $permissionCompanyId, $request) {
            $target->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            $this->syncBranches($target, $permissionCompanyId, $branchIds, $request->user()->id);
            $this->syncUserPermissions($request, $target, $permissionCompanyId);
            $this->audit($request, PartnerAdminAuditEvent::UPDATED_USER_BRANCHES, $target, [
                'branches' => $branchIds,
            ]);
        });

        return redirect()
            ->route('partner.admin.users.edit', $target)
            ->with('status', 'Usuário atualizado.');
    }

    public function disableUser(Request $request, User $user): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.disable'), 403);

        $companyId = $this->companyId($request);
        $target = $this->targetUser($user, $companyId);

        abort_if($target->id === $request->user()->id, 403);

        $target->delete();
        $this->audit($request, 'disabled_user', $target);

        return redirect()
            ->route('partner.admin.users')
            ->with('status', 'Usuário desativado.');
    }

    public function resetUserPassword(Request $request, string $user): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.update'), 403);

        $companyId = $this->companyId($request);
        $target = $this->targetUser(User::withTrashed()->findOrFail($user), $companyId);

        abort_if($target->trashed(), 403);

        $target->forceFill([
            'password' => Hash::make(123456),
            'first_pass' => true,
        ])->save();

        $this->audit($request, 'reset_user_password', $target);

        return redirect()
            ->route('partner.admin.users.edit', $target)
            ->with('status', 'Senha redefinida para 123456.');
    }

    public function auditEvents(Request $request): ViewContract
    {
        $companyId = $this->permissionCompanyId($request);

        return view('partner.admin.audit', [
            'managedCompany' => Company::withTrashed()->find($companyId),
            'events' => PartnerAdminAuditEvent::query()
                ->with(['actor', 'target'])
                ->where('company_id', $companyId)
                ->orderByDesc('created_at')
                ->paginate(30),
        ]);
    }

    public function importTemplate(Request $request): BinaryFileResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.template_export'), 403);

        $company = Company::withTrashed()->findOrFail($this->permissionCompanyId($request));

        return Excel::download(new PartnerUserImportTemplateExport($company), 'modelo-cadastro-usuarios-parceira.xlsx');
    }

    public function previewImport(Request $request): ViewContract
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.bulk_import'), 403);

        $companyId = $this->companyId($request);
        $permissionCompanyId = $this->permissionCompanyId($request);
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
        ]);

        $import = new PartnerUserBulkImport();
        Excel::import($import, $request->file('file'));
        $preview = $this->buildImportPreview($import->rows, $permissionCompanyId);
        $token = (string) Str::uuid();

        session()->put("partner_user_import.{$token}", $preview['valid_rows']);

        return view('partner.admin.import-preview', [
            'token' => $token,
            'managedCompany' => Company::withTrashed()->find(PartnerAccessGate::permissionCompanyIdFor($request->user())),
            'preview' => $preview,
        ]);
    }

    public function confirmImport(Request $request): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.bulk_import'), 403);

        $companyId = $this->companyId($request);
        $permissionCompanyId = $this->permissionCompanyId($request);
        $token = $request->validate(['token' => ['required', 'string']])['token'];
        $rows = session()->pull("partner_user_import.{$token}", []);

        abort_if(empty($rows), 422);

        $created = DB::transaction(function () use ($rows, $companyId, $permissionCompanyId, $request) {
            $count = 0;

            foreach ($rows as $row) {
                $contract = Contract::query()->with('services')->find($row['contract_id']);

                abort_unless($contract, 422);

                $user = User::query()->create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Hash::make(123456),
                    'first_pass' => true,
                    'onlyparner' => true,
                    'user' => true,
                    'company_id' => $companyId,
                ]);

                $user->Companies()->syncWithoutDetaching([$companyId]);
                $user->Employee()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'contract_id' => $contract->id,
                        'service_id' => $contract->services->first()?->uuid,
                    ]
                );
                $this->syncBranches($user, $permissionCompanyId, [$row['branch_id']], $request->user()->id);
                $count++;
            }

            $this->audit($request, PartnerAdminAuditEvent::BULK_IMPORTED_USERS, null, [
                'created' => $count,
                'rows' => collect($rows)->map(fn ($row) => [
                    'email' => $row['email'],
                    'branch_id' => $row['branch_id'],
                    'contract_id' => $row['contract_id'],
                ])->values()->all(),
            ]);

            return $count;
        });

        return redirect()
            ->route('partner.admin.users')
            ->with('status', "{$created} usuário(s) importado(s).");
    }

    private function companyId(Request $request): string
    {
        $companyId = PartnerAccessGate::companyIdFor($request->user());

        abort_unless($companyId, 403);

        return $companyId;
    }

    private function permissionCompanyId(Request $request): string
    {
        $companyId = PartnerAccessGate::permissionCompanyIdFor($request->user());

        abort_unless($companyId, 403);

        return $companyId;
    }

    private function validateUserData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email' . ($user ? ',' . $user->id : '')],
            'branches' => ['array'],
            'branches.*' => ['integer'],
            'user_permissions' => ['array'],
            'user_permissions.*' => ['boolean'],
        ]);
    }

    private function editablePermissionKeys(string $companyId)
    {
        $adminKeys = collect(['admin'])
            ->merge(PartnerPermissionCatalog::itemKeysForGroup(PartnerPermissionCatalog::GROUP_ADMIN));

        return PartnerAccessGate::grantedPermissionKeysForCompany($companyId)
            ->diff($adminKeys)
            ->values();
    }

    private function editablePermissionCatalog(string $companyId): array
    {
        $catalog = PartnerPermissionCatalog::filterGroupsByKeys($this->editablePermissionKeys($companyId));
        unset($catalog[PartnerPermissionCatalog::GROUP_ADMIN]);

        return $catalog;
    }

    private function userPermissionValues(User $target, string $companyId): array
    {
        return $this->editablePermissionKeys($companyId)
            ->mapWithKeys(fn (string $permissionKey) => [
                $permissionKey => PartnerAccessGate::allows($target, $permissionKey),
            ])
            ->all();
    }

    private function branchesFor(string $permissionCompanyId)
    {
        return Andresscompany::query()
            ->with('Company:id,name,parent_id')
            ->whereIn('company_id', $this->branchCompanyIdsFor($permissionCompanyId))
            ->orderBy('city')
            ->orderBy('street')
            ->get();
    }

    private function targetUser(User $user, string $companyId): User
    {
        $managedCompanyId = PartnerAccessGate::permissionCompanyIdFor(auth()->user());
        $companyIds = $managedCompanyId ? $this->branchCompanyIdsFor($managedCompanyId) : [$companyId];

        abort_unless(
            $user->onlyparner && in_array($user->company_id, $companyIds, true),
            403
        );

        return $user;
    }

    private function validBranchIds(Request $request, string $companyId): array
    {
        $ids = collect($request->input('branches', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $valid = Andresscompany::query()
            ->whereIn('company_id', $this->branchCompanyIdsFor($companyId))
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        abort_unless($valid->count() === $ids->count(), 403);

        return $valid->all();
    }

    private function branchCompanyIdsFor(string $permissionCompanyId): array
    {
        return Company::query()
            ->where('id', $permissionCompanyId)
            ->orWhere('parent_id', $permissionCompanyId)
            ->pluck('id')
            ->all();
    }

    private function syncBranches(User $user, string $companyId, array $branchIds, string $actorUserId): void
    {
        PartnerUserBranch::query()
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->delete();

        foreach ($branchIds as $branchId) {
            PartnerUserBranch::query()->create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'created_by' => $actorUserId,
            ]);
        }
    }

    private function syncUserPermissions(Request $request, User $target, string $companyId): void
    {
        if (!$target->exists || !$request->has('user_permissions')) {
            return;
        }

        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_user_exceptions.manage'), 403);

        $allowedKeys = $this->editablePermissionKeys($companyId);
        $submitted = collect($request->input('user_permissions', []));

        foreach ($allowedKeys as $permissionKey) {
            PartnerUserPermissionException::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'user_id' => $target->id,
                    'permission_key' => $permissionKey,
                ],
                [
                    'enabled' => filter_var($submitted->get($permissionKey, false), FILTER_VALIDATE_BOOLEAN),
                    'reason' => 'Configurado no cadastro do usuário.',
                    'created_by' => $request->user()->id,
                ]
            );
        }
    }

    private function buildImportPreview(array $rows, string $companyId): array
    {
        $seenEmails = [];
        $validRows = [];
        $items = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $name = trim((string) ($row['nome'] ?? $row['name'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $branchName = trim((string) ($row['filial'] ?? $row['branch'] ?? ''));
            $errors = [];

            if ($name === '' && $email === '' && $branchName === '') {
                continue;
            }

            if ($name === '') {
                $errors[] = 'Nome obrigatório.';
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email inválido.';
            } elseif (isset($seenEmails[$email])) {
                $errors[] = 'Email duplicado no arquivo.';
            } elseif (User::query()->where('email', $email)->exists()) {
                $errors[] = 'Email já cadastrado.';
            }

            $seenEmails[$email] = true;
            $branch = $this->findBranchByLabel($branchName, $companyId);

            if (!$branch) {
                $errors[] = 'Filial não encontrada na empresa.';
            }

            $contract = $branch ? $this->defaultContractForCompany($branch->company_id) : null;

            if ($branch && !$contract) {
                $errors[] = 'Empresa/Filial sem contrato válido para associar ao usuário.';
            }

            $valid = empty($errors);
            $item = compact('line', 'name', 'email', 'branchName', 'errors', 'valid');
            $items[] = $item;

            if ($valid) {
                $validRows[] = [
                    'name' => $name,
                    'email' => $email,
                    'branch_id' => $branch->id,
                    'contract_id' => $contract->id,
                ];
            }
        }

        return [
            'items' => $items,
            'valid_rows' => $validRows,
            'valid_count' => count($validRows),
            'error_count' => collect($items)->where('valid', false)->count(),
        ];
    }

    private function findBranchByLabel(string $label, string $companyId): ?Andresscompany
    {
        if ($label === '') {
            return null;
        }

        $companyIds = $this->branchCompanyIdsFor($companyId);

        if (ctype_digit($label)) {
            return Andresscompany::query()
                ->whereIn('company_id', $companyIds)
                ->where('id', (int) $label)
                ->first();
        }

        $needle = Str::lower($label);

        return Andresscompany::query()
            ->whereIn('company_id', $companyIds)
            ->get()
            ->first(function (Andresscompany $branch) use ($needle) {
                $labels = collect([
                    $branch->city,
                    $branch->street,
                    $branch->complement,
                    trim("{$branch->city} {$branch->street} {$branch->complement}"),
                ])->filter()->map(fn ($value) => Str::lower(trim($value)));

                return $labels->contains($needle);
            });
    }

    private function defaultContractForCompany(string $companyId): ?Contract
    {
        $company = Company::withTrashed()->find($companyId);
        $companyIds = collect([$companyId, $company?->parent_id])->filter()->all();

        return Contract::query()
            ->whereIn('company_id', $companyIds)
            ->where(fn ($query) => $query
                ->whereNull('date_end')
                ->orWhereDate('date_end', '>=', now()->toDateString()))
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();
    }

    private function audit(Request $request, string $eventType, ?User $target = null, array $payload = []): void
    {
        PartnerAdminAuditEvent::query()->create([
            'company_id' => $this->permissionCompanyId($request),
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $target?->id,
            'event_type' => $eventType,
            'payload' => $payload,
        ]);
    }
}
