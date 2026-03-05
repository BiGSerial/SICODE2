<?php

use App\Http\Controllers\Config\ConfigController;
use App\Http\Controllers\{AdminController, BtzeroController, ConstructionController, CustomAuthController, DispatchController, EngineerController, FilesController, ImpersonationController, MonitorController, PartnerController, PdfController, ProtestController, ReportsController, ResponsibleController, ServicesController, SystemController, TesteController, CancellationController};
use App\Models\Protest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Artisan, Auth, Route};

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
Route::get('/profile/{id}', [App\Http\Controllers\HomeController::class, 'profile'])->middleware('auth')->name('profile');


Route::get('/company', [App\Http\Controllers\HomeController::class, 'company'])->middleware('auth')->name('company');

Route::prefix('/admin')->controller(AdminController::class)->name('admin.')->middleware('auth')->group(function () {

    Route::prefix('/user')->name('user.')->group(function () {
        Route::get('/list', 'user_list')->name('list');
        Route::get('/hierarchy', 'user_hierarchy')->name('hierarchy');
    });

    Route::prefix('/company')->name('company.')->group(function () {
        Route::get('/list', 'company_list')->name('list');
        Route::get('/contracts', 'company_contracts_list')->name('contracts_list');
    });

    Route::prefix('/category')->name('category.')->group(function () {
        Route::get('/', 'category_main')->name('main');
    });

    Route::prefix('/cancellation_categories')->name('cancellation_categories.')->group(function () {
        Route::get('/', 'cancellation_categories')->name('main');
    });

    Route::prefix('/audits')->name('audits.')->group(function () {
        Route::get('/notes', 'audit_notes')->name('notes');
    });

    Route::prefix('/control')->name('control.')->middleware('can:superadm')->group(function () {
        Route::get('/d5', 'control_d5')->name('d5');
        Route::get('/viability', 'control_viability')->name('viability');
        Route::get('/notes', 'control_notes')->name('notes');
        Route::get('/workreports', 'control_workreports')->name('workreports');
        Route::get('/ads_requests', 'control_ads_requests')->name('ads_requests');
    });

    Route::post('/change_pass', 'change_password')->name('change_pass');
});

Route::prefix('/config')->controller(ConfigController::class)->name('config.')->middleware('auth')->middleware('can:admin')->group(function () {
    Route::get('/', 'main')->name('main');
    Route::prefix('/system')->name('system.')->group(function () {
        Route::get('/status', 'systemStatus')->name('status');
        Route::get('/history', 'systemHistory')->name('history');
    });
    Route::get('/services', 'services')->name('services');
    Route::get('/ads-request-recipients', 'adsRequestRecipients')->name('ads_request_recipients');
    Route::prefix('/system')->name('system.')->group(function () {
        Route::get('/jobs_view', 'jobs_view')->name('jobs_view');
        Route::post('/jobs_view/restart', function (Request $request) {
            Artisan::call('queue:restart');
            return response()->json(['ok' => true, 'message' => 'queue:restart enviado']);
        })->middleware('can:superadm', 'throttle:2,1')->name('restart_jobs');
    });
});

Route::prefix('/services/{service}')->controller(ServicesController::class)->name('services.')->middleware('auth')->middleware('check.service.dispatch:services')->group(function () {
    Route::get('/', 'main')->name('main');
    Route::get('/production/{prod}')->name('production');
    Route::get('/to_accompany', 'accompany')->name('accompany');
    Route::get('/my_historic', 'historic')->name('historic');
    Route::get('/waiting_d5_create', 'waiting_d5_create')->name('waiting_d5_create');
    Route::get('/waiting_list', 'waiting_list')->name('waiting');
    Route::get('/hiringSurvey', 'hiringsurvey')->name('hiringsurvey');
    Route::get('/waiting_return', 'waiting_return')->name('waiting_return');
    Route::get('/protocolNote/{note}', 'protocolNote')->name('protocolNote');
    Route::get('/ads_requests', 'adsRequests')->name('ads.requests');

    Route::prefix('/protests')->name('protests.')->group(function () {
        Route::get('/list', 'protests_list')->name('list');
        Route::get('/closed', 'protests_closed')->name('closed');
        Route::get('/view/{protest}', 'protests_view')->name('view');
    });

    Route::prefix('/cancellations')->name('cancellations.')->group(function () {
        Route::get('/queue', 'cancellation_exec_queue')->name('queue');
        Route::get('/ongoing', 'cancellation_exec_ongoing')->name('ongoing');
        Route::get('/ongoing/{request}', 'cancellation_exec_show')->whereNumber('request')->name('ongoing.show');
        Route::get('/ongoing-bulk', 'cancellation_exec_bulk')->name('ongoing.bulk');
        Route::get('/history', 'cancellation_exec_history')->name('history');
    });

    Route::prefix('/externo')->name('oexterno.')->group(function () {
        Route::get('/undefined', 'oexterno_undefined')->name('undefined');
        Route::get('/waiting_payment', 'oexterno_waiting_payment')->name('waiting_payment');
        Route::get('/waiting_orgao', 'oexterno_waiting_orgao')->name('waiting_orgao');
        Route::get('/waiting_taxa', 'oexterno_waiting_taxa')->name('waiting_taxa');
        Route::get('/dashboard', 'oexterno_dashboard')->name('dashboard')->middleware('can:management');
    });

});

Route::prefix('/construction/{service}')->controller(ConstructionController::class)->name('construction.')->middleware('auth')->middleware('check.service.dispatch:services')->group(function () {
    Route::get('/', 'main')->name('main');
    Route::get('/production/{prod}')->name('production');
    Route::get('/to_accompany', 'accompany')->name('accompany');
    Route::get('/my_historic', 'historic')->name('historic');
    Route::get('/viab_returned', 'returned')->name('returned');
    Route::get('/waiting_list', 'waiting')->name('waiting');
    Route::get('/lookatnotes', 'lookatnotes')->name('lookatnotes');


    Route::prefix('/responser')->name('responser.')->group(function () {
        Route::get('/', 'responser_main')->name('main');
    });
});

Route::prefix('/dispatch/{service}')->controller(DispatchController::class)->name('dispatch.')->middleware('auth')->middleware('check.service.dispatch:services')->group(function () {
    Route::get('/main', 'survey_main')->name('main');
    Route::get('/stack', 'survey_stack')->name('stack');
    Route::get('/stack2', 'survey_stack2')->name('stack2');
    Route::get('/transfer', 'survey_transfer')->name('transprod');
    Route::get('/intern_returns', 'returnD5')->name('d5');
    Route::get('/map_info', 'survey_map')->name('mapinfo');
    Route::get('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/waitingFiveNote', 'waitingFiveNote')->name('waitingFiveNote');
    Route::get('/ads_requests', 'adsRequests')->name('ads.requests');
    Route::get('/cancellations/queue', 'cancellationQueue')->name('cancellation.queue');
    Route::get('/cancellations/categories', 'cancellationCategories')->name('cancellation.categories');
    Route::get('/cancellations/history', 'cancellationHistory')->name('cancellation.history');
    Route::get('/cancellations/{request}', 'cancellationShow')->whereNumber('request')->name('cancellation.show');
});

Route::prefix('/monitor')->controller(MonitorController::class)->name('monitor.')->middleware('auth')->middleware('can:management')->group(function () {
    Route::get('/service', 'services')->name('services');
    Route::get('/inconsistency', 'inconsistency')->name('inconsistency');
    Route::get('/analises', 'analises')->name('analises');
    Route::get('/logupdates', 'logger')->name('logsupdate');
});

Route::prefix('/reports')->controller(ReportsController::class)->name('reports.')->middleware('auth')->group(function () {
    Route::get('/productions', 'productions')->middleware('can:management')->name('productions');
    Route::get('/viabilies', 'viabilities')->middleware('can:management')->name('viabilities');
    Route::get('/return_intern/dashboard', 'return_intern_dashboard')->middleware('can:management')->name('return_intern_dashboard');
    Route::get('/return_intern/list', 'return_intern_list')->middleware('can:management')->name('return_intern_list');
    Route::get('/workreports', 'workreports')->name('workreport');
    Route::get('/informe_ads_tacita', 'informeAdsTacita')->name('informe_ads_tacita');
    Route::get('/ads_solicitadas', 'adsSolicitadas')->name('ads_solicitadas');
    Route::get('/rejeceted_workreports', 'rejectedWorkReports')->name('rejecetedWorkreport');
    Route::get('/search', 'search')->name('search');
    Route::get('/advancedsearch', 'advancedsearch')->name('advancedsearch');
    Route::get('/consulta_d5', 'consulta_d5')->name('consulta_d5');
    Route::get('/lookatnotes', 'lookatnotes')->name('lookatnotes');
    Route::get('/equipments', 'equipments')->name('equipments');
    Route::get('/historic_reject_reports', 'historicRejectReports')->name('historicRejectReports');
    Route::get('/return_work_reports', 'returnWorkReports')->middleware('can:management')->name('return_work_reports');
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

Route::middleware('auth')->group(function () {
    Route::get('impersonate/{userId}', [ImpersonationController::class, 'impersonate'])->name('impersonate');
    Route::get('stop-impersonating', [ImpersonationController::class, 'stopImpersonating'])->name('stopImpersonating');
});

Route::prefix('/cancelamentos')->controller(CancellationController::class)->middleware('auth')->name('cancellations.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/historico', 'history')->name('history');
    Route::get('/{request}', 'show')->whereNumber('request')->name('show');
});


Route::prefix('/responsible')->controller(ResponsibleController::class)->middleware(['can:responsible'])->name('responsible.')->group(function () {
    Route::get('/', 'main')->name('main');

    Route::get('/validacao', 'approve_list')->name('validation');
    Route::get('/viabilidade', 'viability_waiting')->name('viability');
    Route::get('/informes', 'inform_obra')->name('informes');
    Route::get('/informes_parciais', 'partial_hist')->name('parciais');
    Route::get('/notas_d5', 'waiting_dfive')->name('d5');

    Route::get('/viab_list', 'viab_list')->name('viab_list');
    Route::get('/viability_waiting', 'viability_waiting')->name('viability_waiting');
    Route::get('/reject_viab', 'viab_reject')->name('rejecte_viab');
    Route::get('/justified_viab', 'justified_viab')->name('justified_viab');
    Route::get('/viab_historico', 'viab_hist')->name('viab_hist');
    Route::get('/informe_obra', 'inform_obra')->name('inform_obra');
    Route::get('/worked_list', 'inform_list')->name('inform_list');
    Route::get('/intern_return', 'intern_return')->name('intern_return');
    Route::get('/approval_list', 'approve_list')->name('approve_list');
    Route::get('/approval_control', 'approve_control')->name('approve_control');
    Route::get('/approval_history', 'approve_hist')->name('approve_hist');
    Route::get('/partial_historic', 'partial_hist')->name('partial_hist');
    Route::get('/waitingDfive', 'waiting_dfive')->name('dfive.waiting');
    Route::get('/ads_requests', 'adsRequests')->name('ads.requests');
});


Route::prefix('/engineers')->controller(EngineerController::class)->middleware(['can:engineer'])->name('engineers.')->group(function () {
    Route::get('/', 'main')->name('main');
    Route::get('/validacao', 'analises_toAnalise')->name('validation');
    Route::get('/viabilidade', 'viability_waiting')->name('viability');
    Route::get('/informes', 'inform_obra')->name('informes');
    Route::get('/informes_parciais', 'waiting_parc')->name('parciais');
    Route::get('/notas_d5', 'waiting_dfive')->name('d5');

    Route::get('/viab_list', 'viab_list')->name('viab_list');
    Route::get('/viability_waiting', 'viability_waiting')->name('viability_waiting');
    Route::get('/reject_viab', 'viab_reject')->name('rejecte_viab');
    Route::get('/justified_viab', 'justified_viab')->name('justified_viab');
    Route::get('/viab_historico', 'viab_hist')->name('viab_hist');
    Route::get('/informe_obra', 'inform_obra')->name('inform_obra');
    Route::get('/worked_list', 'inform_list')->name('inform_list');
    Route::get('/intern_return', 'intern_return')->name('intern_return');
    Route::get('/viability_reports', 'viability_reports')->name('viabilityreports');
    Route::get('/waiting_inform_parc', 'waiting_parc')->name('info.parcial');
    Route::get('/hist_inform_parc', 'hist_parc')->name('hist.parcial');
    Route::get('/waitingDfive', 'waiting_dfive')->name('dfive.waiting');
    Route::get('/ads_requests', 'adsRequests')->name('ads.requests');
    Route::get('/ads_situation', 'adsSituation')->name('ads.situation');
    Route::get('/cancelamentos/aprovacoes', 'cancellationApprovals')->name('cancellations.index');
    Route::get('/cancelamentos/aprovacoes/historico', 'cancellationApprovalsHistory')->name('cancellations.history');
    Route::get('/cancelamentos/aprovacoes/{request}', 'cancellationApprovalShow')->whereNumber('request')->name('cancellations.show');

    Route::prefix('/analises')->name('analises.')->group(function () {
        Route::get('/dashboard', 'analises_dashboard')->name('dashboard');
        Route::get('/toAnalise', 'analises_toAnalise')->name('toAnalise');
        Route::get('/inAnalise', 'analises_inAnalise')->name('inAnalise');
        Route::get('/analised', 'analises_analised')->name('analised');
    });

    Route::prefix('/dashboards')->name('dashboard.')->group(function () {
        Route::get('/final_inform_dashboard', 'conclusion_dash')->name('conclusion_inform');

    });

});


// Partners Route's
Route::prefix('/partner')->controller(PartnerController::class)->name('partner.')->middleware('auth')->group(function () {
    Route::get('/', 'main')->name('main.viability');
    Route::get('/search-notes', 'searchNotes')->name('search.notes');
    Route::get('/todo-viability', 'viability')->name('todo.viability');
    // Route::get('/hired-viability', 'hired_viability')->name('hired.viability');
    Route::get('/historic-viability', 'historic_viab')->name('hist.viability');
    Route::get('/workreport', 'workreport')->name('report.workreport');
    Route::get('/workedlist', 'workedlist')->name('report.workedlist');
    Route::get('/rejectedWorked', 'rejectedWorked')->name('report.rejectedWorked');
    Route::get('/rejected_viability_list', 'rejectedViabList')->name('rejected.viability');
    Route::get('/tacit_viab_list', 'tacitViabList')->name('tacit.viability');
    Route::get('/declared_eqipment', 'declaredEquipment')->name('declared.equipment');
    Route::get('/partialreport', 'partialreport')->name('report.partial');
    Route::get('/partialreportlist', 'partialreportlist')->name('report.partiallist');
    Route::get('/send_ads_form', 'sendAdsForm')->name('report.sendAdsForm');
    Route::get('/ads_requests', 'adsRequests')->name('ads.requests');
    Route::get('/search_notes', 'searchNotes')->name('search.notes');

    Route::prefix('/note_d5')->name('note_d5.')->group(function () {
        Route::get('/list', 'partner_d5_list')->name('list');
        Route::get('/returned', 'partner_d5_returned')->name('returned');
        Route::get('/historic', 'partner_d5_historic')->name('historic');
    });
});

Route::prefix('/btzero')->controller(BtzeroController::class)->name('btzero.')->middleware('auth')->group(function () {
    Route::get('/', 'main')->name('main');
    Route::get('/btzero_report', 'btzeroReport')->name('btzeroReport');
    Route::get('/hist_inform', 'histInform')->name('histInform');
    Route::get('/smc_rejecteds', 'SmcRejecteds')->name('smcRejecteds');
});

// System Manager
Route::prefix('/system')->controller(SystemController::class)->name('system.')->middleware('auth')->middleware('can:superadm')->group(function () {
    Route::get('/', 'commands')->name('main');
    Route::post('/commands/execute', 'execute')->name('artisan.execute');
    Route::get('/commands/status/{pid}', 'checkStatus')->name('artisan.status');

});


Route::prefix('/protests')->controller(ProtestController::class)->name('protests.')->middleware('auth')->group(function () {
    Route::get('/common/overview', 'common_overview')->name('common.overview');
    Route::get('/common/note/{note}', 'common_note')->name('common.note');

    Route::prefix('/services')->name('services.')->group(function () {
        Route::get('/', 'main')->name('main');
        Route::get('/view/{jobId}', 'view')->name('view');
        Route::get('/view_controller/{jobId}', 'view_controller')->name('view_controller');
        Route::get('/view_only/{medProtestId}', 'view_only')->name('view_only');
        Route::get('/accompany', 'accompany')->name('accompany');
        Route::get('/history', 'history')->name('history');
    });

    Route::prefix('/dispatch')->name('dispatch.')->group(function () {
        Route::get('/', 'dispatch_lists')->name('lists');
        Route::get('/view/{protest}', 'dispatch_view')->name('view');
        Route::get('/view_only/{protest}', 'dispatch_view_only')->name('view_only');
        Route::get('/closeds', 'dispatch_closeds')->name('closeds');
        Route::get('/config_users', 'dispatch_config_users')->name('config_users');
        Route::get('/per_user', 'dispatch_per_user')->name('per_user');
        Route::get('/monitoring', 'dispatch_monitoring')->name('monitoring');
    });

    Route::prefix('/dispatch-btzero')->name('dispatch_btzero.')->group(function () {
        Route::get('/', 'dispatch_btzero_lists')->name('lists');
        Route::get('/view/{protest}', 'dispatch_view')->name('view');
        Route::get('/view_only/{protest}', 'dispatch_view_only')->name('view_only');
        Route::get('/closeds', 'dispatch_btzero_closeds')->name('closeds');
        Route::get('/config_users', 'dispatch_config_users')->name('config_users');
        Route::get('/per_user', 'dispatch_per_user')->name('per_user');
        Route::get('/monitoring', 'dispatch_btzero_monitoring')->name('monitoring');
    });

    Route::prefix('/partner')->name('partner.')->group(function () {
        Route::get('/', 'partner_main')->name('main');
        Route::get('/view/{medProtestId}', 'partner_view')->name('view');
        Route::get('/view_only/{medProtestId}', 'partner_view_only')->name('view_only');
        Route::get('/history', 'partner_history')->name('history');

    });

    Route::get('/dashboard', 'dashboard')->middleware('can:management')->name('dashboard');
    Route::get('/print/{medProtestId}', 'print')->name('print');
});





Route::prefix('/PDF')->controller(PdfController::class)->name('pdf.')->middleware('auth')->group(function () {
    Route::get('/chkList_FTVEO/{id?}', 'checkList')->name('checklist');
    Route::get('/chkListFiscal/{id?}', 'checkListFiscal')->name('checklistFiscal');
});


// Files Controller Manager
Route::prefix('/files')->controller(FilesController::class)->name('files.')->group(function () {
    Route::get('/', 'main')->name('main');
    Route::get('/files/{file}/preview', 'preview')->name('preview');
    Route::get('/files/{file}/download', 'download')->name('download');
    Route::get('/files/zip', 'zipSelected')->name('zip');
});

Route::get('/info', function () {
    return phpinfo();
});
