<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BreachedEmail;

class DarkWebMonitoringController extends Controller
{
    public function index(){

        $company_id = auth()->user()->company_id;
        $breachedEmails = BreachedEmail::with('userData')->where('company_id', $company_id)->get();
        return view('darkweb', compact('breachedEmails'));
    }
}
