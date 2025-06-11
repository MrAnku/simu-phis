<?php

namespace App\Http\Controllers\Api;

use App\Models\Policy;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiPolicyController extends Controller
{
    public function addPolicy(Request $request)
    {
        try {
            $request->validate([
                'policy_name' => 'required|string|max:255',
                'policy_description' => 'required|string',
                'policy_file' => 'required|file|mimes:pdf|max:10240',
            ]);

            $file = $request->file('policy_file');

            // Generate a random name for the file
            $randomName = generateRandom(32);
            $extension = $file->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;

            $filePath = $request->file('policy_file')->storeAs('/uploads/policyFile', $newFilename, 's3');

            $policy = Policy::create([
                'policy_name' => $request->policy_name,
                'policy_description' => $request->policy_description,
                'policy_file' => "/" . $filePath,
                'company_id' => Auth::user()->company_id,
            ]);
            log_action("Policy created for company : " . Auth::user()->company_name);
            return response()->json(['status' => 'success','message' => 'Policy added successfully'], 201);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
