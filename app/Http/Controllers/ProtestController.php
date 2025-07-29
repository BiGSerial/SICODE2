<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProtestController extends Controller
{
    public function main()
    {
        return view('protest.main');
    }

    public function view($medProtestId)
    {
        return view('protest.view', ['medProtestId' => $medProtestId]);
    }

    public function view_only($medProtestId)
    {
        return view('protest.view_only', ['medProtestId' => $medProtestId]);
    }

    public function accompany()
    {
        return view('protest.accompany');
    }

    public function history()
    {
        return view('protest.history');
    }
}
