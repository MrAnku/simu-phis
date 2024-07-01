<?php

namespace App\Http\Controllers;

use App\Models\PhishingEmail;
use Illuminate\Http\Request;

class PhishingEmailsController extends Controller
{
    //
    public function index(){
        $phishingEmails = PhishingEmail::with(['web', 'sender_p'])->get();

        // dd($phishingEmails);
        return view('phishingEmails', compact('phishingEmails'));
    }
}
