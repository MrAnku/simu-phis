<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\PhishingEmail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiPhishingEmailsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $phishingEmails = PhishingEmail::with('web', 'sender_p')->where('company_id', $companyId)->orWhere('company_id', 'default')->paginate(10);

            if ($phishingEmails->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => __('No phishing emails found.'),
                ], 404);
            }


            return response()->json([
                'status' => true,
                'message' => __('Phishing emails retrieved successfully.'),
                'data' => $phishingEmails,
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => __('Error retrieving phishing emails') . " :" . $th->getMessage(),
            ], 500);
        }
    }

    public function searchPhishingMaterial(Request $request)
    {
        try {
            $searchTerm = $request->input('name');
            $companyId = Auth::user()->company_id;

            $phishingEmails = PhishingEmail::where('company_id', $companyId)
                ->orWhere('company_id', 'default')
                ->where(function ($query) use ($searchTerm) {
                    $query->where('name', 'LIKE', "%{$searchTerm}%");
                })
                ->get();

            if ($phishingEmails->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => __('No phishing emails found.'),
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => __('Phishing emails retrieved successfully.'), 
                'data' => $phishingEmails
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => __('Error') . " :" . $th->getMessage(),
            ], 500);
        }
    }
}
