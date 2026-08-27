<?php

namespace App\Http\Controllers;

class PurchasesExpensesHubController extends Controller
{
    public function index()
    {
        return view('admin.purchases-expenses.index');
    }
}
