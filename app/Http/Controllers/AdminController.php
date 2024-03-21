<?php

namespace App\Http\Controllers;

class AdminController extends Controller
{
    public function user_list()
    {
        return view('admin.users.list');
    }

    public function company_list()
    {
        return view('admin.Company.list');
    }

    public function company_contracts_list()
    {
        return view('admin.Company.contract_list');
    }
}
