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

    public function category_main()
    {
        return view('admin.category.main');
    }

    public function audit_notes()
    {
        return view('admin.audits.notes');
    }


    // User Hierarchy View
    public function user_hierarchy()
    {
        return view('admin.users.hierarchy');
    }
}
