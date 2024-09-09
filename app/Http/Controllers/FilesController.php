<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FilesController extends Controller
{
    public function main()
    {
        return view('files.managerfiles');
    }
}
