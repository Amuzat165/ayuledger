<?php

namespace App\Http\Controllers;

class ReportsHubController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }
}