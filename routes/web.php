<?php

use App\Http\Controllers\Config\ConfigController;
use App\Http\Controllers\{AdminController, ConstructionController, CustomAuthController, DispatchController, MonitorController, PartnerController, ReportsController, ServicesController, TesteController};
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\{Auth, Route};

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

        if (Auth()->User()->onlyparner) {
            return redirect()->route('partner.main.viability');
        } else {
            return redirect('home');
        }

    }

    return view('auth.login');
});

Auth::routes();

// Route::prefix('/login')->controller(CustomAuthController::class)->name('login.')->group(function () {
//     Route::post('/', 'login')->name('login');
//     Route::get('/logout', 'logout')->name('logout');
//     Route::get('/change_pass', 'showChangePass')->name('show.change');


// });

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth')->name('home');



Route::get('/company', [App\Http\Controllers\HomeController::class, 'company'])->middleware('auth')->name('company');

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
    Route::get('/viab_returned', 'returned')->name('returned');
    Route::get('/waiting_list', 'waiting')->name('waiting');
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
    Route::get('/intern_returns', 'returnD5')->name('d5');
    Route::get('/map_info', 'survey_map')->name('mapinfo');
});



Route::prefix('/partner')->controller(PartnerController::class)->name('partner.')->middleware('auth')->group(function () {
    Route::get('/', 'viability')->name('main.viability');
    Route::get('/todo-viability', 'viability')->name('todo.viability');
    Route::get('/hired-viability', 'hired_viability')->name('hired.viability');
    Route::get('/historic-viability', 'historic_viab')->name('hist.viability');

});

Route::prefix('/forms')->name('forms.')->middleware('auth')->group(function () {
    Route::get('/viability/{id?}', App\Http\Livewire\Partner\Forms\Viability::class)->name('viability');
});


Route::prefix('/testes')->controller(TesteController::class)->name('tests.')->group(function () {
    Route::get('/testes', 'productions')->middleware('can:superadm')->name('productions');
    Route::get('/page', 'page')->name('page');
    Route::get('/pdf', 'pdf')->name('pdf');
    Route::get('/design', function () {
        return View('desingtestview');
    });
});
