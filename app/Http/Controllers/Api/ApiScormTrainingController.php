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
                'scorm_version' => 'required|string|in:1.2,2004',
                'scorm_file' => 'required|file|mimes:zip|max:921600',
                'passing_score' => 'required|numeric',
                'entry_point' => 'nullable|string|max:255',
            ]);

            $companyId = Auth::user()->company_id;
            $file = $request->file('scorm_file');
            $slug = Str::slug($request->name) . '-' . time();

            // Save zip temporarily for scanning
            $tmpZipPath = sys_get_temp_dir() . '/' . uniqid('scorm_', true) . '.zip';
            $file->move(dirname($tmpZipPath), basename($tmpZipPath));

            $zip = new ZipArchive;
            if ($zip->open($tmpZipPath) === TRUE) {
                // Scan for malicious files
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    $filename = strtolower($stat['name']);
                    if (preg_match('/\.(php|exe|sh|bat|py|pl|cgi|asp|jsp|wsf|vbs|dll|com|scr|pif|jar|msi|cmd)$/i', $filename)) {
                        $zip->close();
                        @unlink($tmpZipPath);
                        return response()->json(['success' => false, 'message' => 'Malicious or executable file detected in SCORM zip.'], 422);
                    }
                }

                // check the entry point exists in the zip
                if ($request->entry_point && !$zip->locateName($request->entry_point)) {
                    $zip->close();
                    @unlink($tmpZipPath);
                    return response()->json(['success' => false, 'message' => 'Entry point file not found in SCORM zip.'], 422);
                }


                $zip->close();

                // S3 folder path
                $scormFolder = "uploads/scorm_package/{$companyId}/{$slug}";
                $scormZipName = $slug . '.zip';
                $scormZipPath = $scormFolder . '/' . $scormZipName;

                // Upload to S3 after scan
                \Storage::disk('s3')->putFileAs($scormFolder, new \Illuminate\Http\File($tmpZipPath), $scormZipName);

                @unlink($tmpZipPath);

                $scormTraining = ScormTraining::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'category' => $request->category,
                    'file_path' => $scormFolder . '/' . $scormZipName, // S3 path
                    'scorm_version' => $request->scorm_version,
                    'company_id' => $companyId,
                    'entry_point' => $request->entry_point,
                    'passing_score' => $request->passing_score,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => __('Scorm Training Created successfully.')
                ], 201);
            } else {
                @unlink($tmpZipPath);
                return response()->json(['success' => false, 'message' => 'Failed to extract SCORM zip file'], 422);
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

    public function viewScormTraining(Request $request)
    {
        try {
            $scormId = $request->id;

            if(!$request->id){
                return response()->json([
                    'success' => false,
                    'message' => __('Scorm ID is required')
                ], 422);
            }
            $scormTraining = getScormTraining($scormId);

            if($scormTraining['status'] == false){
                return response()->json([
                    'success' => false,
                    'message' => $scormTraining['msg']
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => __('Scorm training retrieved successfully'),
                'data' => $scormTraining
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
