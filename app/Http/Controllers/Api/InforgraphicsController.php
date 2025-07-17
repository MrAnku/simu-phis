<?php

namespace App\Http\Controllers\Api;

use App\Models\Inforgraphic;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InforgraphicsController extends Controller
{
    public function index()
    {
        try{
            $infographics = Inforgraphic::where('company_id', Auth::user()->company_id)
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json([
                'success' => true,
                'data' => $infographics
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch infographics'], 500);
        }
    }

    public function saveInfographics(Request $request)
    {
        try{
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|string|max:255',
                'file' => 'required|file|mimes:jpg,jpeg,png|max:2048',
            ]);

            $file = $request->file('file');
            $filePath = $file->storeAs(
                '/uploads/inforgraphics',
                uniqid() . '.' . $file->getClientOriginalExtension(),
                's3'
            );
            Inforgraphic::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'category' => $data['category'],
                'file_path' => "/" . $filePath,
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inforgraphic saved successfully'
            ]);

            
        }catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->validator->errors()->first(),
            ], 422);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 422);
        }
    }
}
