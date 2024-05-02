<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomAuthController extends Controller
{
    /**
     * As Regras de Logout sao gerenciadas pelo Laravel UI
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();

            if (Auth::User()->first_pass) {
                return redirect()->route('login.show.change');
            }

            if (Auth::User()->onlyparner) {
                return redirect()->route('partner.main.viability');
            }

            return redirect()->intended('/home');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ]);
    }

    public function showChangePass()
    {
        if (Auth::User()->first_pass) {
            return view('auth.change');
        } else {
            return redirect()->back();
        }
    }

}
