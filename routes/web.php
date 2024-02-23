<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Config\Config;
use App\Http\Controllers\Config\ConfigController;
use App\Http\Controllers\Construction;
use App\Http\Controllers\ConstructionController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\TesteController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {

    if (Auth::check()) {
        return redirect('home');
    }

    return view('auth.login');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::prefix('/admin')->controller(AdminController::class)->name('admin.')->middleware('auth')->group(function () {

    Route::prefix('/user')->name('user.')->group(function () {
        Route::get('/list', 'user_list')->name('list');
    });

    Route::prefix('/company')->name('company.')->group(function () {
        Route::get('/list', 'company_list')->name('list');
        Route::get('/contracts', 'company_contracts_list')->name('contracts_list');
    });

    Route::post('/change_pass', 'change_password')->name('change_pass');
});

Route::prefix('/config')->controller(ConfigController::class)->name('config.')->middleware('auth')->middleware('can:admin')->group(function () {
    Route::get('/', 'main')->name('main');
    Route::get('/services', 'services')->name('services');
});

Route::prefix('/services/{service}')->controller(ServicesController::class)->name('services.')->middleware('auth')->middleware('can:user')->group(function () {
    Route::get('/', 'main')->name('main');
    Route::get('/production/{prod}')->name('production');
    Route::get('/to_accompany', 'accompany')->name('accompany');
    Route::get('/my_historic', 'historic')->name('historic');
    Route::get('/waiting_list', 'waiting_list')->name('waiting');
});

Route::prefix('/construction/{service}')->controller(ConstructionController::class)->name('construction.')->middleware('auth')->middleware('can:user')->group(function () {
    Route::get('/', 'main')->name('main');
    Route::get('/production/{prod}')->name('production');
    Route::get('/to_accompany', 'accompany')->name('accompany');
    Route::get('/my_historic', 'historic')->name('historic');
    Route::get('/waiting_list', 'waiting_list')->name('waiting');
});

Route::prefix('/monitor')->controller(MonitorController::class)->name('monitor.')->middleware('auth')->middleware('can:management')->group(function () {
    Route::get('/service', 'services')->name('services');
    Route::get('/inconsistency', 'inconsistency')->name('inconsistency');
    Route::get('/analises', 'analises')->name('analises');
    Route::get('/logupdates', 'logger')->name('logsupdate');
});

Route::prefix('/reports')->controller(ReportsController::class)->name('reports.')->middleware('auth')->group(function () {
    Route::get('/productions', 'productions')->middleware('can:management')->name('productions');
    Route::get('/search', 'search')->name('search');
    Route::get('/advancedsearch', 'advancedsearch')->name('advancedsearch');
});

Route::prefix('/dispatch/{service}')->controller(DispatchController::class)->name('dispatch.')->middleware('auth')->middleware('can:operator')->group(function () {
    Route::get('/main', 'survey_main')->name('main');
    Route::get('/stack', 'survey_stack')->name('stack');
    Route::get('/transfer', 'survey_transfer')->name('transprod');
    Route::get('/map_info', 'survey_map')->name('mapinfo');
});

Route::prefix('/tests')->controller(TesteController::class)->name('tests.')->middleware('can:superadm')->group(function () {
    Route::get('/testes', 'productions')->name('productions');
    Route::get('/page', 'page')->name('page');
    Route::get('/pdf', 'pdf')->name('pdf');
});
