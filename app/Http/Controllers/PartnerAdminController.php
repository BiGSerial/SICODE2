<?php

namespace App\Http\Controllers;

use App\Exports\Partner\PartnerUserImportTemplateExport;
use App\Imports\PartnerUserBulkImport;
use App\Models\Andresscompany;
use App\Models\PartnerAdminAuditEvent;
use App\Models\PartnerRole;
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
        $companyId = $this->companyId($request);

        $users = User::query()
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('Companies', fn ($companyQuery) => $companyQuery->where('companies.id', $companyId));
            })
            ->orderBy('name')
            ->paginate(25);

        return view('partner.admin.users', [
            'users' => $users,
        ]);
    }

    public function createUser(Request $request): ViewContract
    {
        return view('partner.admin.user-form', [
            'user' => new User(),
            'branches' => $this->branchesFor($request),
            'selectedBranches' => collect(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.create'), 403);

        $companyId = $this->companyId($request);
        $data = $this->validateUserData($request);
        $branchIds = $this->validBranchIds($request, $companyId);

        $user = DB::transaction(function () use ($data, $branchIds, $companyId, $request) {
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
            $this->syncBranches($user, $companyId, $branchIds, $request->user()->id);
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
        $target = $this->targetUser($user, $companyId);

        return view('partner.admin.user-form', [
            'user' => $target,
            'branches' => $this->branchesFor($request),
            'selectedBranches' => $target->partnerBranches()
                ->where('company_id', $companyId)
                ->pluck('branch_id'),
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.update'), 403);

        $companyId = $this->companyId($request);
        $target = $this->targetUser($user, $companyId);
        $data = $this->validateUserData($request, $target);
        $branchIds = $this->validBranchIds($request, $companyId);

        DB::transaction(function () use ($target, $data, $branchIds, $companyId, $request) {
            $target->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            $this->syncBranches($target, $companyId, $branchIds, $request->user()->id);
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

    public function permissions(Request $request): ViewContract
    {
        $companyId = $this->companyId($request);
        $role = PartnerRole::query()
            ->where('company_id', $companyId)
            ->with('permissions')
            ->first();

        return view('partner.admin.permissions', [
            'catalog' => PartnerPermissionCatalog::groups(),
            'configured' => (bool) $role,
            'permissions' => $role?->permissions?->keyBy('permission_key') ?? collect(),
        ]);
    }

    public function updatePermissions(Request $request): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_permissions.update'), 403);

        $companyId = $this->companyId($request);
        $allowedKeys = PartnerPermissionCatalog::allPermissionKeys();
        $enabledKeys = collect($request->input('permissions', []))
            ->filter(fn ($enabled) => filter_var($enabled, FILTER_VALIDATE_BOOLEAN))
            ->keys()
            ->intersect($allowedKeys)
            ->values();

        $role = PartnerRole::query()->firstOrCreate(
            ['company_id' => $companyId],
            ['name' => 'Padrão']
        );

        foreach ($allowedKeys as $permissionKey) {
            $role->permissions()->updateOrCreate(
                ['permission_key' => $permissionKey],
                [
                    'scope_type' => array_key_exists($permissionKey, PartnerPermissionCatalog::groups()) ? 'group' : 'item',
                    'enabled' => $enabledKeys->contains($permissionKey),
                ]
            );
        }

        PartnerAdminAuditEvent::query()->create([
            'company_id' => $companyId,
            'actor_user_id' => $request->user()->id,
            'event_type' => PartnerAdminAuditEvent::UPDATED_PERMISSIONS,
            'payload' => [
                'enabled_permissions' => $enabledKeys->all(),
            ],
        ]);

        return redirect()
            ->route('partner.admin.permissions')
            ->with('status', 'Permissões atualizadas.');
    }

    public function exceptions(Request $request): ViewContract
    {
        $companyId = $this->companyId($request);

        return view('partner.admin.exceptions', [
            'users' => $this->companyUsers($companyId)->get(),
            'catalog' => PartnerPermissionCatalog::groups(),
            'exceptions' => PartnerUserPermissionException::query()
                ->with(['user', 'creator'])
                ->where('company_id', $companyId)
                ->orderByDesc('updated_at')
                ->paginate(25),
        ]);
    }

    public function storeException(Request $request): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_user_exceptions.manage'), 403);

        $companyId = $this->companyId($request);
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'permission_key' => ['required', 'string'],
            'enabled' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless(PartnerPermissionCatalog::allPermissionKeys()->contains($data['permission_key']), 422);
        $target = $this->targetUser(User::query()->findOrFail($data['user_id']), $companyId);

        PartnerUserPermissionException::query()->updateOrCreate(
            [
                'company_id' => $companyId,
                'user_id' => $target->id,
                'permission_key' => $data['permission_key'],
            ],
            [
                'enabled' => (bool) $data['enabled'],
                'reason' => $data['reason'] ?? null,
                'created_by' => $request->user()->id,
            ]
        );

        $this->audit($request, 'updated_user_permission_exception', $target, [
            'permission_key' => $data['permission_key'],
            'enabled' => (bool) $data['enabled'],
        ]);

        return redirect()
            ->route('partner.admin.exceptions')
            ->with('status', 'Exceção atualizada.');
    }

    public function destroyException(Request $request, PartnerUserPermissionException $exception): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_user_exceptions.manage'), 403);

        $companyId = $this->companyId($request);
        abort_unless($exception->company_id === $companyId, 403);

        $targetUserId = $exception->user_id;
        $payload = $exception->only(['permission_key', 'enabled']);
        $exception->delete();

        $this->audit($request, 'deleted_user_permission_exception', null, array_merge($payload, [
            'target_user_id' => $targetUserId,
        ]));

        return redirect()
            ->route('partner.admin.exceptions')
            ->with('status', 'Exceção removida.');
    }

    public function auditEvents(Request $request): ViewContract
    {
        $companyId = $this->companyId($request);

        return view('partner.admin.audit', [
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

        return Excel::download(new PartnerUserImportTemplateExport(), 'modelo-cadastro-usuarios-parceira.xlsx');
    }

    public function previewImport(Request $request): ViewContract
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.bulk_import'), 403);

        $companyId = $this->companyId($request);
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
        ]);

        $import = new PartnerUserBulkImport();
        Excel::import($import, $request->file('file'));
        $preview = $this->buildImportPreview($import->rows, $companyId);
        $token = (string) Str::uuid();

        session()->put("partner_user_import.{$token}", $preview['valid_rows']);

        return view('partner.admin.import-preview', [
            'token' => $token,
            'preview' => $preview,
        ]);
    }

    public function confirmImport(Request $request): RedirectResponse
    {
        abort_unless(PartnerAccessGate::allows($request->user(), 'admin_users.bulk_import'), 403);

        $companyId = $this->companyId($request);
        $token = $request->validate(['token' => ['required', 'string']])['token'];
        $rows = session()->pull("partner_user_import.{$token}", []);

        abort_if(empty($rows), 422);

        $created = DB::transaction(function () use ($rows, $companyId, $request) {
            $count = 0;

            foreach ($rows as $row) {
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
                $this->syncBranches($user, $companyId, [$row['branch_id']], $request->user()->id);
                $count++;
            }

            $this->audit($request, PartnerAdminAuditEvent::BULK_IMPORTED_USERS, null, [
                'created' => $count,
                'rows' => collect($rows)->map(fn ($row) => [
                    'email' => $row['email'],
                    'branch_id' => $row['branch_id'],
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

    private function validateUserData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email' . ($user ? ',' . $user->id : '')],
            'branches' => ['array'],
            'branches.*' => ['integer'],
        ]);
    }

    private function branchesFor(Request $request)
    {
        return Andresscompany::query()
            ->where('company_id', $this->companyId($request))
            ->orderBy('city')
            ->orderBy('street')
            ->get();
    }

    private function companyUsers(string $companyId)
    {
        return User::query()
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('Companies', fn ($companyQuery) => $companyQuery->where('companies.id', $companyId));
            })
            ->orderBy('name');
    }

    private function targetUser(User $user, string $companyId): User
    {
        abort_unless(
            $user->company_id === $companyId || $user->Companies()->where('companies.id', $companyId)->exists(),
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
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        abort_unless($valid->count() === $ids->count(), 403);

        return $valid->all();
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

            $valid = empty($errors);
            $item = compact('line', 'name', 'email', 'branchName', 'errors', 'valid');
            $items[] = $item;

            if ($valid) {
                $validRows[] = [
                    'name' => $name,
                    'email' => $email,
                    'branch_id' => $branch->id,
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

        if (ctype_digit($label)) {
            return Andresscompany::query()
                ->where('company_id', $companyId)
                ->where('id', (int) $label)
                ->first();
        }

        $needle = Str::lower($label);

        return Andresscompany::query()
            ->where('company_id', $companyId)
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

    private function audit(Request $request, string $eventType, ?User $target = null, array $payload = []): void
    {
        PartnerAdminAuditEvent::query()->create([
            'company_id' => $this->companyId($request),
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $target?->id,
            'event_type' => $eventType,
            'payload' => $payload,
        ]);
    }
}
