<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin')->only(['impersonate']);
    }

    public function impersonate($userId)
    {
        $user = User::find($userId);

        if ($user) {
            Session::put('impersonate', Auth()->User()->id);
            // session(['impersonate' => Auth()->User()->id]);
            Auth()->login($user);

            return redirect('/')->with('success', 'Você está acessando como ' . $user->name);
        }

        return redirect()->back()->with('error', 'Usuario não Encontrado.');
    }

    public function stopImpersonating()
    {
        $originalUserId = Session::get('impersonate');

        if (!$originalUserId) {
            return redirect('/')->with('error', 'Sessão de impersonate não encontrada.');
        }

        // Limpa a marca de impersonate mesmo em caso de inconsistência.
        Session::forget('impersonate');

        $originalUser = User::withTrashed()->find($originalUserId);

        if ($originalUser) {
            Auth()->login($originalUser);

            return redirect('/')->with('success', 'Você parou de se passar por alguém');
        }

        return redirect('/')->with('error', 'Usuário original não encontrado.');
    }
}
