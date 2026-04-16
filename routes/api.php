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

// Route::post('login', function (Request $r) {
//     $r->validate(['email' => 'required|email','password' => 'required']);
//     $user = \App\Models\User::where('email', $r->email)->first();
//     if (! $user || ! Hash::check($r->password, $user->password)) {
//         return response()->json(['message' => 'Credenciais inválidas'], 401);
//     }
//     return ['token' => $user->createToken('sicode-token')->plainTextToken];
// });


// Testes
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::get('/reports/production-wall', \App\Http\Controllers\Api\Reports\ProductionWallDataController::class)
        ->middleware(['web', 'auth', 'can:superadm'])
        ->name('api.v1.reports.production_wall');
    Route::get('/reports/walls/{wall}/production-v2', \App\Http\Controllers\Api\Reports\ProductionWallV2DataController::class)
        ->middleware(['web', 'auth', 'can:superadm'])
        ->whereNumber('wall')
        ->name('api.v1.reports.production_wall_v2');
    Route::get('/reports/walls/{wall}/production-v2/{screen}', \App\Http\Controllers\Api\Reports\ProductionWallV2ScreenDataController::class)
        ->middleware(['web', 'auth', 'can:superadm'])
        ->whereNumber('wall')
        ->whereNumber('screen')
        ->name('api.v1.reports.production_wall_v2.screen');
    Route::get('/reports/walls/{wall}/production-v2/{screen}/items/{serviceId}/charts', \App\Http\Controllers\Api\Reports\ProductionWallV2ItemChartsController::class)
        ->middleware(['web', 'auth', 'can:superadm'])
        ->whereNumber('wall')
        ->whereNumber('screen')
        ->name('api.v1.reports.production_wall_v2.item_charts');
    Route::get('/reports/walls/{wall}/production-v2/{screen}/fixed/project-review', \App\Http\Controllers\Api\Reports\ProductionWallV2FixedProjectReviewController::class)
        ->middleware(['web', 'auth', 'can:superadm'])
        ->whereNumber('wall')
        ->whereNumber('screen')
        ->name('api.v1.reports.production_wall_v2.fixed.project_review');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
        Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

        // Usuarios
        Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
        Route::get('/services/{service}/payments', [\App\Http\Controllers\Api\DispatchPaymentController::class, 'index']);



    });



});
