<?php

namespace App\Http\Controllers;

use App\Models\MedProtest;
use App\Models\Note;
use Illuminate\Http\Request;

class ProtestController extends Controller
{
    public function main()
    {
        return view('protest.services.main');
    }

    public function view($medProtestId)
    {
        return view('protest.services.view', ['medProtestId' => $medProtestId]);
    }

    public function view_controller($medProtestId)
    {
        return view('protest.services.viewUpper', ['medProtestId' => $medProtestId]);
    }

    public function view_only($medProtestId)
    {
        return view('protest.services.view_only', ['medProtestId' => $medProtestId]);
    }

    public function accompany()
    {
        return view('protest.services.accompany');
    }

    public function history()
    {
        return view('protest.services.history');
    }

    public function print(int $medProtestId = 1)
    {
        $medProtest = MedProtest::with('protest', 'EvidenceFiles', 'assignments')->find($medProtestId);

        return view('protest.print', ['medProtest' => $medProtest]);
    }



    // Dispatch Section
    public function dispatch_lists()
    {
        return view('protest.dispatch.list');
    }

    public function dispatch_btzero_lists()
    {
        return view('protest.dispatch.list', [
            'isBtzeroDispatch' => true,
        ]);
    }

    public function dispatch_view($medProtestId)
    {
        return view('protest.dispatch.view', ['medProtestId' => $medProtestId]);
    }

    public function dispatch_view_only($medProtestId)
    {
        return view('protest.dispatch.view_only', ['medProtestId' => $medProtestId]);
    }

    public function dispatch_closeds()
    {
        return view('protest.dispatch.closed');
    }

    public function dispatch_btzero_closeds()
    {
        return view('protest.dispatch.closed', [
            'isBtzeroDispatch' => true,
        ]);
    }

    public function dispatch_config_users()
    {
        return view('protest.dispatch.config_users');
    }

    public function dispatch_per_user()
    {
        return view('protest.dispatch.per-user');
    }

    public function dispatch_monitoring()
    {
        return view('protest.dispatch.monitoring');
    }

    public function dispatch_btzero_monitoring()
    {
        return view('protest.dispatch.monitoring', [
            'isBtzeroDispatch' => true,
        ]);
    }

    //Parner Section
    public function partner_main()
    {
        return view('protest.partner.main');
    }

    public function partner_view($medProtestId)
    {
        return view('protest.partner.view', ['medProtestId' => $medProtestId]);
    }

    public function partner_view_only($medProtestId)
    {
        return view('protest.partner.view_only', ['medProtestId' => $medProtestId]);
    }

    public function partner_history()
    {
        return view('protest.partner.history');
    }


    public function dashboard()
    {
        return view('protest.dispatch.dashboard');
    }

    public function common_overview()
    {
        return view('protest.common.overview');
    }

    public function common_note(Note $note)
    {
        $note->load([
            'FiveNote:id,note_id,note_d5,visible_partner,is_completed,is_supervisioned,is_archived,is_payed,completed_at',
            'Protests' => function ($query) {
                $query->with([
                    'medProtests' => function ($med) {
                        $med->with([
                            'ProtestJobs' => function ($job) {
                                $job->with(['owner:id,name', 'creator:id,name'])->orderByDesc('created_at');
                            },
                            'Assignments.user:id,name',
                            'EvidenceFiles',
                            'Comments.user:id,name',
                        ])->orderByDesc('dtCriacaoMedida');
                    },
                    'Comments.user:id,name',
                ])->orderByDesc('dtAberturaNota');
            },
        ]);

        if ($note->Protests->isEmpty()) {
            abort(404);
        }

        return view('protest.common.note-overview', [
            'note'      => $note,
            'protests'  => $note->Protests,
        ]);
    }

}
