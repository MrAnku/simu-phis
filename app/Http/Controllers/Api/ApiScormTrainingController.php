<?php

namespace App\Http\Controllers\Api;

use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ScormTraining;
use App\Models\ScormAssignedUser;
use App\Http\Controllers\Controller;
use App\Models\AiCallCampLive;
use App\Models\CampaignLive;
use App\Models\QuishingLiveCamp;
use App\Models\WaLiveCampaign;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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


            $extractTo = $request->file('scorm_file')->storeAs("uploads/scorm_package/{$companyId}", $slug, 's3');

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
                        'file_path' => "/uploads/scorm_package/{$companyId}/{$slug}/",
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

            if ($scormTrainings->isEmpty()) {
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

    public function deleteScormTrainings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scormId' => 'required|integer|exists:scorm_trainings,id',
            'scorm_package' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $scormId = $request->input('scormId');
        $scorm_package = $request->input('scorm_package');
        $company_id = Auth::user()->company_id;

        try {
            // Start DB transaction
            DB::beginTransaction();

            $emailCampExists = CampaignLive::where('scorm_training', $scormId)->exists();
            $aiCallCampExists = AiCallCampLive::where('scorm_training', $scormId)->exists();
            $quishCampExists = QuishingLiveCamp::where('scorm_training', $scormId)->exists();
            $waLiveCampExists = WaLiveCampaign::where('scorm_training', $scormId)->exists();

            if ($emailCampExists || $aiCallCampExists || $quishCampExists || $waLiveCampExists) {
                return response()->json([
                    'success' => false,
                    'message' => "Campaigns are associated with this scorm, Delete Campaigns first",
                ], 422);
            }

            // Delete assigned users
            DB::table('blue_collar_scorm_assigned_users')->where('scorm', $scormId)->where('company_id', $company_id)->delete();
            DB::table('scorm_assigned_users')->where('scorm', $scormId)->where('company_id', $company_id)->delete();

            // Delete SCORM
            $scormTraining = ScormTraining::where('id', $scormId)->where('company_id', $company_id)->first();

            $isDeleted  = $scormTraining->delete();

            // Delete scorm package





            DB::commit(); // Commit transaction
            log_action("Scorm deleted");

            return response()->json([
                'success' => true,
                'message' => __('Scorm deleted successfully'),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack(); // Rollback on error
            log_action("Failed to Scorm training : " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __("Something went wrong, please try again later."),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
