<?php

namespace App\Http\Controllers;

class ProjectReviewController extends Controller
{
    public function list()
    {
        return view('project-review.list');
    }

    public function dashboard()
    {
        return view('project-review.dashboard');
    }

    public function history()
    {
        return view('project-review.history');
    }

    public function categories()
    {
        return view('project-review.categories');
    }
}
