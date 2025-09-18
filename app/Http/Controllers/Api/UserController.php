<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * GET /api/v1/users
     * Query params:
     *  - q: string (busca por nome/email)
     *  - per_page: int (padrão 20, máx 100)
     *  - page: int
     *  - sort: campo (name|email|created_at)
     *  - dir: asc|desc
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q'        => ['nullable','string','max:255'],
            'per_page' => ['nullable','integer','min:1','max:100'],
            'page'     => ['nullable','integer','min:1'],
            'sort'     => ['nullable','in:name,email,created_at'],
            'dir'      => ['nullable','in:asc,desc'],
        ]);

        $q       = $validated['q'] ?? null;
        $sort    = $validated['sort'] ?? 'created_at';
        $dir     = $validated['dir']  ?? 'desc';
        $perPage = $validated['per_page'] ?? 20;

        // (opcional) escapar curingas para evitar “%” vindo do usuário
        $safeQ = $q ? str_replace(['%','_'], ['\%','\_'], $q) : null;

        $query = \App\Models\User::query()
            ->when($safeQ, function ($qbuilder) use ($safeQ) {
                $qbuilder->where(function ($w) use ($safeQ) {
                    $w->where('name', 'like', "%{$safeQ}%")
                      ->orWhere('email', 'like', "%{$safeQ}%");
                });
            })
            ->orderBy($sort, $dir);

        $paginator = $query->paginate($perPage)->withQueryString();

        return \App\Http\Resources\UserResource::collection($paginator);
    }

}
