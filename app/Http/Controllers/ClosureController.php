<?php

namespace App\Http\Controllers;

class ClosureController extends Controller
{
    public function overview()
    {
        return view('closure.overview');
    }

    public function meta()
    {
        return view('closure.meta');
    }

    public function passive()
    {
        return view('closure.passive');
    }

    public function orderDetail(int $order)
    {
        return view('closure.order-detail', ['orderId' => $order]);
    }
}
