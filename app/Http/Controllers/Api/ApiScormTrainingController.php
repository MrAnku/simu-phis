<?php

namespace App\Http\Controllers\Api;

use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ScormTraining;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiScormTrainingController extends Controller
{
    public function addScormTraining(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string',
                'scorm_file' => 'required|file|mimes:zip|max:921600',
                'passing_score' => 'required|numeric',
                'entry_point' => 'nullable|string|max:255',
            ]);

            $companyId = Auth::user()->company_id;

            $file = $request->file('scorm_file');

            $slug = Str::slug($request->name) . '-' . time();

            $randomName = generateRandom(10);
            $extension = $request->file('scorm_file')->getClientOriginalExtension();
            $darkLogoFilename = $randomName . '.' . $extension;


            $extractTo = $request->file('scorm_file')->storeAs("uploads/scorm_package/{$companyId}", $slug, 'local');

            if ($extractTo) {
                // Extract ZIP
                $zip = new ZipArchive;
                if ($zip->open($file) === TRUE) {
                    $zip->extractTo($extractTo);
                    $zip->close();

                    $scormTraining = ScormTraining::create([
                        'name' => $request->name,
                        'description' => $request->description,
                        'category' => $request->category,
                        'file_path' => "uploads/scorm_package/{$companyId}/{$slug}/",
                        'company_id' => $companyId,
                        'entry_point' => $request->entry_point,
                        'passing_score' => $request->passing_score,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => __('Scorm Training Created successfully.')
                    ], 201);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to create Scorm Training'], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchScormTrainings(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $scormTrainings = ScormTraining::where('company_id', $companyId)
                ->get();

                if($scormTrainings->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('No Scorm trainings found for this company.')
                    ], 422);
                }

            return response()->json([
                'success' => true,
                'message' => __('Scorm trainings retrieved successfully'),
                'data' => $scormTrainings
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
}
