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
}
