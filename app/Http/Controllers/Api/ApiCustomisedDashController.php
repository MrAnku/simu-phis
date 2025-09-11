<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CustomisedDashboards;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiCustomisedDashController extends Controller
{
    public function index()
    {
        try {
            $dashboard = CustomisedDashboards::where('company_id', Auth::user()->company_id)->first();
            return response()->json([
                'success' => true,
                'message' => 'Customised Dashboard Data',
                'data' => $dashboard
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateOrCreate(Request $request)
    {
        try {
            $request->validate([
                'layout_json' => 'required|array',
            ]);

            CustomisedDashboards::updateOrCreate(
                ['company_id' => Auth::user()->company_id],
                ['layout_json' => $request->layout_json]
            );

            return response()->json([
                'success' => true,
                'message' => 'Dashboard Updated Successfully'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
