<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CancellationController extends Controller
{
    public function index(Request $request)
    {
        return view('cancellations.create');
    }

    public function history(Request $request)
    {
        return view('cancellations.my');
    }

    public function show(Request $request)
    {
        return view('cancellations.show', [
            'request' => $request->route('request'),
        ]);
    }
}
