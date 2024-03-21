<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;

class ConfigController extends Controller
{
    public function main()
    {
        return view('config.main');
    }

    public function services()
    {
        return view('config.services.main');
    }
}
