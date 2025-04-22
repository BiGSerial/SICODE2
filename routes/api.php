<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login', function (Request $r) {
    $r->validate(['email' => 'required|email','password' => 'required']);
    $user = \App\Models\User::where('email', $r->email)->first();
    if (! $user || ! Hash::check($r->password, $user->password)) {
        return response()->json(['message' => 'Credenciais inválidas'], 401);
    }
    return ['token' => $user->createToken('sicode-token')->plainTextToken];
});


// Testes
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
