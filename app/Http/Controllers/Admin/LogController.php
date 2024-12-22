<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(){
        $allLogs = Log::orderBy('id', 'desc')->get();
        return view('admin.logs', compact('allLogs'));
    }
}
