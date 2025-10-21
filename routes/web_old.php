<?php

use App\Http\Controllers\Config\ConfigController;
use App\Http\Controllers\{AdminController, ConstructionController, CustomAuthController, DispatchController, FilesController, ImpersonationController, MonitorController, PartnerController, ReportsController, ResponsibleController, ServicesController, TesteController};
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

Route::middleware(['auth'])->group(function () {

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/company', [App\Http\Controllers\HomeController::class, 'company'])->name('company');


    Route::prefix('/admin')->controller(AdminController::class)->name('admin.')->group(function () {
        Route::prefix('/user')->name('user.')->group(function () {
            Route::get('/list', 'user_list')->name('list');
        });
        Route::prefix('/company')->name('company.')->group(function () {
            Route::get('/list', 'company_list')->name('list');
            Route::get('/contracts', 'company_contracts_list')->name('contracts_list');
        });
        Route::post('/change_pass', 'change_password')->name('change_pass');
    });

    Route::prefix('/config')->controller(ConfigController::class)->name('config.')->middleware('can:admin')->group(function () {
        Route::get('/', 'main')->name('main');
        Route::get('/services', 'services')->name('services');
    });

    Route::prefix('/services/{service}')->controller(ServicesController::class)->name('services.')->middleware('auth')->group(function () {
        Route::get('/', 'main')->name('main');
        Route::get('/production/{prod}')->name('production');
        Route::get('/to_accompany', 'accompany')->name('accompany');
        Route::get('/my_historic', 'historic')->name('historic');
        Route::get('/waiting_list', 'waiting_list')->name('waiting');
        Route::get('/hiringSurvey', 'hiringsurvey')->name('hiringsurvey');
    });

    Route::prefix('/construction/{service}')->controller(ConstructionController::class)->name('construction.')->middleware('auth')->group(function () {
        Route::get('/', 'main')->name('main');
        Route::get('/production/{prod}')->name('production');
        Route::get('/to_accompany', 'accompany')->name('accompany');
        Route::get('/my_historic', 'historic')->name('historic');
        Route::get('/viab_returned', 'returned')->name('returned');
        Route::get('/waiting_list', 'waiting')->name('waiting');


        Route::prefix('/responser')->name('responser.')->group(function () {
            Route::get('/', 'responser_main')->name('main');
        });
    });

    Route::prefix('/dispatch/{service}')->controller(DispatchController::class)->name('dispatch.')->middleware('auth')->group(function () {
        Route::get('/main', 'survey_main')->name('main');
        Route::get('/stack', 'survey_stack')->name('stack');
        Route::get('/transfer', 'survey_transfer')->name('transprod');
        Route::get('/intern_returns', 'returnD5')->name('d5');
        Route::get('/map_info', 'survey_map')->name('mapinfo');
    });

    Route::prefix('/monitor')->controller(MonitorController::class)->name('monitor.')->middleware('can:management')->group(function () {
        Route::get('/service', 'services')->name('services');
        Route::get('/inconsistency', 'inconsistency')->name('inconsistency');
        Route::get('/analises', 'analises')->name('analises');
        Route::get('/logupdates', 'logger')->name('logsupdate');
    });

    Route::prefix('/reports')->controller(ReportsController::class)->name('reports.')->group(function () {
        Route::get('/productions', 'productions')->middleware('can:management')->name('productions');
        Route::get('/viabilies', 'viabilities')->middleware('can:management')->name('viabilities');

        Route::get('/workreports', 'workreports')->name('workreport');
        Route::get('/rejeceted_workreports', 'rejectedWorkReports')->name('rejecetedWorkreport');
        Route::get('/search', 'search')->name('search');
        Route::get('/advancedsearch', 'advancedsearch')->name('advancedsearch');
    });

    Route::prefix('/forms')->name('forms.')->group(function () {
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

    Route::middleware('auth')->group(function () {
        Route::get('impersonate/{userId}', [ImpersonationController::class, 'impersonate'])->name('impersonate');
        Route::get('stop-impersonating', [ImpersonationController::class, 'stopImpersonating'])->name('stopImpersonating');
    });


    Route::prefix('/responsible')->controller(ResponsibleController::class)->middleware(['can:responsible'])->name('responsible.')->group(function () {
        Route::get('/', 'main')->name('main');
        Route::get('/viab_list', 'viab_list')->name('viab_list');
        Route::get('/viability_waiting', 'viability_waiting')->name('viability_waiting');
        Route::get('/reject_viab', 'viab_reject')->name('rejecte_viab');
        Route::get('/justified_viab', 'justified_viab')->name('justified_viab');
        Route::get('/viab_historico', 'viab_hist')->name('viab_hist');

    });


    // Partners Route's
    Route::prefix('/partner')->controller(PartnerController::class)->name('partner.')->group(function () {
        Route::get('/', 'main')->name('main.viability');
        Route::get('/todo-viability', 'viability')->name('todo.viability');
        // Route::get('/hired-viability', 'hired_viability')->name('hired.viability');
        Route::get('/historic-viability', 'historic_viab')->name('hist.viability');
        Route::get('/workreport', 'workreport')->name('report.workreport');
        Route::get('/workedlist', 'workedlist')->name('report.workedlist');
        Route::get('/rejectedWorked', 'rejectedWorked')->name('report.rejectedWorked');
        Route::get('/rejected_viab_list', 'rejectedViabList')->name('rejected.viability');
        Route::get('/tacit_viab_list', 'tacitViabList')->name('tacit.viability');
        Route::get('/declared_eqipment', 'declaredEquipment')->name('declared.equipment');

    });

    // Files Controller Manager
    Route::prefix('/files')->controller(FilesController::class)->name('files.')->group(function () {
        Route::get('/', 'main')->name('main');
        Route::get('/files/{file}/download', 'download')->name('download');
        Route::get('/files/zip', 'zipSelected')->name('zip');
    });



});


Route::get('/info', function () {
    return phpinfo();
});
