<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CancellationController extends Controller
{
    public function create(Request $request)
    {
        return view('cancellations.create');
    }

    public function my(Request $request)
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
