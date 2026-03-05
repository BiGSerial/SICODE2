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

    public function control_d5()
    {
        return view('admin.control.d5');
    }

    public function control_viability()
    {
        return view('admin.control.viability');
    }

    public function control_notes()
    {
        return view('admin.control.notes');
    }

    public function control_workreports()
    {
        return view('admin.control.workreports');
    }

    public function control_ads_requests()
    {
        return view('admin.control.ads_requests');
    }

    public function cancellation_categories()
    {
        return view('admin.cancellation_categories');
    }
}
