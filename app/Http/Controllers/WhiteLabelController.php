<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhiteLabelledCompany;
use App\Models\WhiteLabelledSmtp;
use Illuminate\Support\Facades\Auth;

class WhiteLabelController extends Controller
{
    public function saveWhiteLabel(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email',
            'domain' => 'required|string',
            'learn_domain' => 'required|string',
            'dark_logo' => 'required|image|mimes:jpeg,png,jpg',
            'light_logo' => 'required|image|mimes:jpeg,png,jpg',
            'favicon' => 'required|image|mimes:jpeg,png,jpg,gif',
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer',
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => 'required|string|max:255',
            'smtp_encryption' => 'required|string',
            'from_address' => 'required',
            'from_name' => 'required|string|max:255',
        ]);

        $isCreatedWhitLabel = WhiteLabelledCompany::create([
            'company_id' => Auth::user()->company_id,
            'company_email' => $request->company_email,
            'domain' => $request->domain,
            'learn_domain' => $request->learn_domain,
            'dark_logo' => $request->dark_logo,
            'light_logo' => $request->light_logo,
            'favicon' => $request->favicon,
            'company_name' => $request->company_name,
            'approved_by_partner' => 0,
            'date' => now(),
        ]);

        if ($isCreatedWhitLabel) {
            WhiteLabelledSmtp::create([
                'smtp_host' => $request->smtp_host,
                'smtp_port' => $request->smtp_port,
                'smtp_username' => $request->smtp_username,
                'smtp_password' => $request->smtp_password,
                'smtp_encryption' => $request->smtp_encryption,
                'from_address' => $request->from_address,
                'from_name' => $request->from_name,
                'company_id' => Auth::user()->company_id,
            ]);
            return redirect()->back()->with('success', 'White label created successfully.');
        }
    }
}
