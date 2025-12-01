<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\CampaignLive;
use App\Models\TrainingGame;
use Illuminate\Http\Request;
use App\Models\AiCallCampLive;
use App\Models\TrainingModule;
use App\Models\WaLiveCampaign;
use App\Models\QuishingLiveCamp;
use App\Models\TprmCampaignLive;
use Illuminate\Http\JsonResponse;
use App\Models\TranslatedTraining;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\SmishingLiveCampaign;
use App\Models\TrainingAssignedUser;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\BlueCollarTrainingUser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiTrainingModuleController extends Controller
{

    public function trainings(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $params = $request->only(['category', 'training_type']);
            $search = $request->query('search');

            $query = TrainingModule::where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhere('company_id', 'default');
            });

            // Add basic filters
            foreach ($params as $key => $value) {
                $query->where($key, $value);
            }

            // Add search filter
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            $trainingModules = $query->paginate(12);

            return response()->json([
                'success' => true,
                'message' => __('Training modules fetched successfully'),
                'data' => $trainingModules,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function trainingPage(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            // List of filterable columns
            $filterable = [
                'category',
                'training_type',
                'core_behaviour',
                'content_type',
                'language',
                'security',
                'role',
                'duration',
                'tags',
                'program_resources',
                'industry',
            ];

            // Collect filters from request
            $filters = $request->only($filterable);

            // Search and pagination
            $search = $request->query('search');
            $perPage = (int) $request->query('per_page', 12);
            $page = (int) $request->query('page', 1);
            $type = $request->query('type');

            if ($type == 'default') {
                $query = TrainingModule::where('company_id', 'default');
            } else if ($type == 'custom') {
                $query = TrainingModule::where('company_id', $companyId);
            } else {
                $query = TrainingModule::where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhere('company_id', 'default');
                });
            }


            // Apply filters
            foreach ($filters as $key => $value) {
                if (!is_null($value) && $value !== '') {
                    $query->where($key, $value);
                }
            }

            // Apply search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
            }

            // Paginate results
            $trainingModules = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => __('Training modules fetched successfully'),
                'data' => $trainingModules,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }




    public function index(Request $request): JsonResponse
    {
        try {
            $company_id = Auth::user()->company_id;
            $trainings = [];

            if ($request->has('type') || $request->has('category')) {
                $selectedType = $request->input('type');
                $selectedCategory = $request->input('category');

                if ($selectedType == 'games') {
                    $trainings = TrainingGame::where(function ($query) use ($company_id) {
                        $query->where('company_id', $company_id)
                            ->orWhere('company_id', 'default');
                    })->paginate(9);
                } else {
                    $trainings = TrainingModule::where('training_type', $selectedType)
                        ->where('category', $selectedCategory)
                        ->where(function ($query) use ($company_id) {
                            $query->where('company_id', $company_id)
                                ->orWhere('company_id', 'default');
                        })->paginate(9);
                }
            } else {
                // Default filter
                $trainings = TrainingModule::where(function ($query) use ($company_id) {
                    $query->where('company_id', $company_id)
                        ->orWhere('company_id', 'default');
                })->where('training_type', 'static_training')
                    ->where('category', 'international')
                    ->paginate(9);
            }

            $trainings->appends($request->except('page'));

            return response()->json([
                'success' => true,
                'message' => __('Fetch All Training Successfully'),
                'trainings' => $trainings,
                'pagination' => [
                    'current_page' => $trainings->currentPage(),
                    'total_pages' => $trainings->lastPage(),
                    'per_page' => $trainings->perPage(),
                    'total' => $trainings->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Training fetch failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Something went wrong, please try again later.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function addTraining(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'moduleName' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'training_type' => 'required|string|max:255',
            'core_behaviour' => 'nullable|string|max:255',
            'content_type' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:255',
            'security' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:255',
            'program_resources' => 'nullable|string|max:255',
            'estimated_time' => 'required|max:255',
            'mPassingScore' => 'required|numeric|min:0|max:100',
            'jsonData' => 'required|json',
            'mCoverFile' => 'nullable|file|mimes:jpg,jpeg,png',
            'mModuleLang' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __("Error: ") .  $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $moduleName = $request->input('moduleName');
            // $category =   $request->input('category');
            $mPassingScore = $request->input('mPassingScore');
            $mModuleLang = $request->input('mModuleLang', 'en');
            $filePath = 'uploads/trainingModule/defaultTraining.jpg';
            $estimated_time = $request->input('estimated_time');
            $jsonData = $request->input('jsonData');
            $companyId = Auth::user()->company_id;


            if ($request->hasFile('mCoverFile')) {
                $file = $request->file('mCoverFile');

                // Generate a random name for the file
                $randomName = generateRandom(32);
                $extension = $file->getClientOriginalExtension();
                $newFilename = $randomName . '.' . $extension;

                $filePath = $request->file('mCoverFile')->storeAs('/uploads/trainingModule', $newFilename, 's3');
            }

            $trainingModule = new TrainingModule([
                'name' => $moduleName,
                'category' => $request->input('category'),
                'training_type' => $request->input('training_type'),
                'core_behaviour' => $request->input('core_behaviour'),
                'content_type' => $request->input('content_type'),
                'language' => $request->input('language'),
                'security' => $request->input('security'),
                'role' => $request->input('role'),
                'industry' => $request->input('industry'),
                'duration' => $request->input('duration'),
                'tags' => $request->input('tags'),
                'program_resources' => $request->input('program_resources'),
                'estimated_time' => $estimated_time,
                'cover_image' => "/" . $filePath,
                'passing_score' => $mPassingScore,
                'json_quiz' => $jsonData,
                'module_language' => $mModuleLang,
                'company_id' => $companyId,
                'alternative_training' => $request->input('alternative_training')
            ]);

            if ($trainingModule->save()) {
                log_action("New training added {$moduleName}");
                return response()->json([
                    'success' => true,
                    'message' => __('Training Added Successfully'),
                    'data' => $trainingModule
                ], 201);
            } else {
                log_action("Failed to add Training");
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to add Training')
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }


    public function addGamifiedTraining(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'module_name' => 'required|string|max:255',
            'passing_score' => 'required|numeric|min:0|max:100',
            'completion_time' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'gamifiedJsonData' => 'required|json',
            'cover_file' => 'nullable|file|mimes:jpg,jpeg,png'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Error') . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }
        try {

            $companyId = Auth::user()->company_id;

            // Handling cover file
            if ($request->hasFile('cover_file')) {
                $file = $request->file('cover_file');

                // Generate a random name for the file
                $randomName = generateRandom(32);
                $extension = $file->getClientOriginalExtension();
                $newFilename = $randomName . '.' . $extension;

                $filePath = $request->file('cover_file')->storeAs('/uploads/trainingModule', $newFilename, 's3');
            } else {
                $filePath = 'uploads/trainingModule/defaultTraining.jpg';
            }

            $trainingModule = new TrainingModule([
                'name' => $request->input('module_name'),
                'estimated_time' => $request->input('completion_time'),
                'cover_image' => "/" . $filePath,
                'passing_score' => $request->input('passing_score'),
                'category' => $request->input('category'),
                'training_type' => 'gamified',
                'json_quiz' => $request->input('gamifiedJsonData'),
                'module_language' => 'en',
                'company_id' => $companyId,
            ]);


            if ($trainingModule->save()) {
                log_action("New gamified training added {$request->module_name}");
                return response()->json([
                    'success' => true,
                    'message' => __('Gamified training added successfully'),
                    'training' => $trainingModule
                ], 201);
            } else {
                log_action("Failed to add Training");
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to add gamified training')
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateGamifiedTraining(Request $request)
    {
        try {
            $request->validate([
                'module_name' => 'required|string|max:255',
                'passing_score' => 'required|numeric|min:0|max:100',
                'completion_time' => 'required|string|max:255',
                'category' => 'nullable|string|max:255',
                'gamifiedJsonData' => 'required|json',
                'gamifiedTrainingId' => 'required|numeric',
                'cover_file' => 'nullable|file|mimes:jpg,jpeg,png'
            ]);

            $company_id = Auth::user()->company_id;

            $updateData = [
                'name' => $request->input('module_name'),
                'estimated_time' => $request->input('completion_time'),
                'passing_score' => $request->input('passing_score'),
                'category' => $request->input('category'),
                'json_quiz' => $request->input('gamifiedJsonData'),
                'module_language' => 'en',
            ];

            $trainingModule = TrainingModule::find($request->input('gamifiedTrainingId'));

            $isTrainingUpdated = TrainingModule::where('id', $request->input('gamifiedTrainingId'))
                ->where('company_id', $company_id)
                ->update($updateData);

            // Handle cover image if provided
            if ($request->hasFile('cover_file')) {

                // Get the previous file path
                $oldFilePath = ltrim($trainingModule->cover_image, '/');

                // Get new file content
                $newFileContent = file_get_contents($request->file('cover_file')->getRealPath());

                // Overwrite the previous file in S3
                Storage::disk('s3')->put($oldFilePath, $newFileContent);

                $isTrainingUpdated = true; // Since we updated the file, consider it as an update
            }

            if ($isTrainingUpdated) {
                TranslatedTraining::where('training_id', $request->input('gamifiedTrainingId'))->delete();
                log_action("Gamified training module updated");
                return response()->json([
                    'success' => true,
                    'message' => __('Gamified training updated successfully')
                ], 200);
            } else {
                log_action("Failed to update Gamified Training");
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to update Gamified Training')
                ], 404);
            }
        } catch (\Exception $e) {
            \Log::error("Error updating gamified training: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __("Something Went Wrong"),
                'error' => $e->getMessage(),
            ], 500); // Internal Server Error
        }
    }




    public function getTrainingById($id)
    {
        try {
            $trainingData = TrainingModule::find($id);

            if ($trainingData) {
                return response()->json([
                    "status" => true,
                    'message' => __('Training Module found'),
                    "all_training_module" => $trainingData
                ], 200); // 200 OK
            } else {
                return response()->json([
                    "status" => false,
                    'message' => __('Training Module not found')
                ], 404); // 404 Not Found
            }
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                'message' => __('An error occurred while fetching the training module'),
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }


    public function getTrainingByType($type)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401); // Unauthorized
            }

            $company_id = $user->company_id;

            $trainings = TrainingModule::where('training_type', $type)
                ->where(function ($query) use ($company_id) {
                    $query->where('company_id', $company_id)
                        ->orWhere('company_id', 'default');
                })->get();

            $internationalTrainings = $trainings->where('category', 'international');
            $middleEastTrainings = $trainings->where('category', 'middle_east');

            $res = [
                'success' => true,
                'message' => __('Training modules fetched successfully'),
                'data' => [
                    'international' => $internationalTrainings->values(),
                    'middle_east' => $middleEastTrainings->values()
                ]
            ];

            return response()->json($res, 200); // OK
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong while fetching training modules'),
                'error' => $e->getMessage()
            ], 500); // Internal Server Error
        }
    }


    public function updateTrainingModule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trainingModuleid' => 'required|integer|exists:training_modules,id',
            'moduleName' => 'required',
            'mPassingScore' => 'required',
            'category' => 'nullable|string|max:255',
            'mModuleLang' => 'nullable|string|max:255',
            'estimated_time' => 'required|numeric',
            'updatedjsonData' => 'required|json',
            'mCoverFile' => 'nullable|file|mimes:jpg,jpeg,png',
            'security' => 'nullable|string|max:255',
            'training_type' => 'nullable|string|max:255',
            'core_behaviour' => 'nullable|string|max:255',
            'content_type' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:255',
            'program_resources' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }
        // return $request;
        $moduleName = $request->input('moduleName');
        $trainingModuleId = $request->input('trainingModuleid');
        $mPassingScore = $request->input('mPassingScore');
        $mModuleLang = $request->input('mModuleLang') ?? 'en';
        $estimated_time = $request->input('estimated_time');
        $jsonData = $request->input('updatedjsonData');
        $programResources = $request->input('program_resources') === 'null' ? null : $request->input('program_resources');
        $company_id = Auth::user()->company_id;

        try {
            $updateData = [
                'name' => $moduleName,
                'estimated_time' => $estimated_time,
                'passing_score' => $mPassingScore,
                'category' => $request->input('category') === 'null' ? null : $request->input('category'),
                'json_quiz' => $jsonData,
                'module_language' => $mModuleLang,
                'security' => $request->input('security') === 'null' ? null : $request->input('security'),
                'training_type' => $request->input('training_type') === 'null' ? null : $request->input('training_type'),
                'core_behaviour' => $request->input('core_behaviour') === 'null' ? null : $request->input('core_behaviour'),
                'content_type' => $request->input('content_type') === 'null' ? null : $request->input('content_type'),
                'role' => $request->input('role') === 'null' ? null : $request->input('role'),
                'industry' => $request->input('industry') === 'null' ? null : $request->input('industry'),
                'duration' => $request->input('duration') === 'null' ? null : $request->input('duration'),
                'tags' => $request->input('tags') === 'null' ? null : $request->input('tags'),
                'language' =>  null,
                'program_resources' => $programResources,
                'alternative_training' => $request->input('alternative_training')
            ];

            $trainingModule = TrainingModule::find($trainingModuleId);

            $isTrainingUpdated = TrainingModule::where('id', $trainingModuleId)->update($updateData);

            if ($request->hasFile('mCoverFile')) {
                // Get the previous file path
                $oldFilePath = ltrim($trainingModule->cover_image, '/');

                // Get new file content
                $newFileContent = file_get_contents($request->file('mCoverFile')->getRealPath());

                // Overwrite the previous file in S3
                Storage::disk('s3')->put($oldFilePath, $newFileContent);

                $isTrainingUpdated = true; // Since we updated the file, consider it as an update
            }

            if ($isTrainingUpdated) {
                TranslatedTraining::where('training_id', $trainingModuleId)->delete();
                log_action("Training module updated");
                return response()->json([
                    'success' => true,
                    'message' => __('Training updated successfully')
                ]);
            } else {
                log_action("Failed to update Training");
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to update Training')
                ], 400);
            }
        } catch (\Exception $e) {
            log_action("Exception while updating Training: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while updating training module'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteTraining(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trainingid' => 'required|integer|exists:training_modules,id',
            'cover_image' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $trainingId = $request->input('trainingid');
        $coverImage = $request->input('cover_image');
        $company_id = Auth::user()->company_id;

        try {

            $emailCampExists = CampaignLive::where('training_module', $trainingId)->where('company_id', $company_id)->exists();
            $aiCallCampExists = AiCallCampLive::where('training_module', $trainingId)->where('company_id', $company_id)->exists();
            $quishingCampExists = QuishingLiveCamp::where('training_module', $trainingId)->where('company_id', $company_id)->exists();
            $smishingCampExists = SmishingLiveCampaign::where('training_module', $trainingId)->where('company_id', $company_id)->exists();
            $tprmCampExists = TprmCampaignLive::where('training_module', $trainingId)->where('company_id', $company_id)->exists();
            $waCampExists = WaLiveCampaign::where('training_module', $trainingId)->where('company_id', $company_id)->exists();

            if ($emailCampExists || $aiCallCampExists || $quishingCampExists || $smishingCampExists || $tprmCampExists || $waCampExists) {
                return response()->json([
                    'success' => false,
                    'message' => __("Campaigns are associated with this training, delete campaigns first"),
                ], 422);
            }

            $trainingAssigned = TrainingAssignedUser::where('training', $trainingId)->where('company_id', $company_id)->exists();
            $blueCollarTrainingAssigned = BlueCollarTrainingUser::where('training', $trainingId)->where('company_id', $company_id)->exists();

            if ($trainingAssigned || $blueCollarTrainingAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => __("This Training is assigned to users, you cannot delete it."),
                ], 422);
            }

            // Delete training module
            $trainingModule = TrainingModule::where('id', $trainingId)->where('company_id', $company_id)->first();

            $isDeleted  = $trainingModule->delete();
            TranslatedTraining::where('training_id', $trainingId)->delete();

            // Delete cover image if not default
            if ($isDeleted && $coverImage !== 'defaultTraining.jpg') {
                // Delete the file from S3
                Storage::disk('s3')->delete($trainingModule->cover_image);
            }

            log_action("Training module deleted");

            return response()->json([
                'success' => true,
                'message' => __('Training deleted successfully'),
            ], 200);
        } catch (Exception $e) {
            log_action("Failed to delete training module: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __("Something went wrong, please try again later."),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function trainingPreview($trainingid): JsonResponse
    {
        try {
            // Decode the training ID
            $training = TrainingModule::find(base64_decode($trainingid));

            // Check if the training module exists
            if (!$training) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid Training Module'),
                ], 404);
            }

            // Prepare the data for the preview
            $response = [
                'success' => true,
                'training' => $training,
                'message' => __('Training preview'),
            ];

            // Check the training type and return the appropriate response
            if ($training->training_type == 'gamified') {
                return response()->json([
                    'success' => true,
                    'message' => __('Gamified training preview'),
                    'data' => $response,
                ]);
            }

            // For other types of training, just return the basic data
            return response()->json([
                'success' => true,
                'message' => __('Training preview'),
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            // Return an error response if something goes wrong
            return response()->json([
                'success' => false,
                'error' => __('Something went wrong, please try again later.'),
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function loadPreviewTrainingContent($trainingid, $lang): JsonResponse
    {
        try {
            // Decode the training ID
            $id = base64_decode($trainingid);

            // Validate the ID
            if ($id === false || !ctype_digit($id)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid training module ID.')
                ], 400);
            }

            // Fetch the training module
            $trainingData = TrainingModule::find($id);

            if (!$trainingData) {
                return response()->json([
                    'success' => false,
                    'message' => __('Training Module Not Found')
                ], 404);
            }

            $moduleLanguage = $lang;

            // For Static Trainings
            if ($trainingData->training_type == 'static_training') {
                if ($moduleLanguage !== 'en') {

                    $translator = new TranslationService();
                    $trainingData = $translator->translateTraining($trainingData, $moduleLanguage);
                    
                }

                return response()->json([
                    'success' => true,
                    'jsonData' => $trainingData,
                    'message'  => __("Training Data Fetch Successfully")
                ]);
            }

            // For Gamified Trainings
            if ($trainingData->training_type == 'gamified') {
                if ($moduleLanguage !== 'en') {
                     $translator = new TranslationService();
                    $trainingData = $translator->translateTraining($trainingData, $moduleLanguage);
                }

                return response()->json([
                    'success' => false,
                    'jsonData' => $trainingData,
                    'message'  => __("Training Data Fetch Successfully")
                ]);
            }

            // Fallback (should not reach here)
            return response()->json([
                'success' => false,
                'message' => __('Unsupported training type.')
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong. Please try again later.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function translateJsonData($json, $lang)
    {
        try {
            $prompt = "Translate the following JSON data to " . langName($lang) . " language. The output should only contain JSON data:\n\n" . json_encode($json);

            $response = Http::withOptions(['verify' => false])->withHeaders([
                'Authorization' => 'Bearer ' . env("OPENAI_API_KEY"),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => __('You are an expert JSON translator. Always provide valid JSON data.')],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1500,
                'temperature' => 0.7,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => $response->body(),
                ]);
            }
            $translatedJson = $response['choices'][0]['message']['content'];
            return response()->json([
                'success' => true,
                'jsonData' => json_decode($translatedJson, true),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function getGames()
    {
        try {
            $companyId = Auth::user()->company_id;
            $search = request()->query('search');
            if ($search) {
                $games = TrainingGame::where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhere('company_id', 'default');
                })
                    ->where('name', 'like', '%' . $search . '%')
                    ->paginate(9);
            } else {
                $games = TrainingGame::where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhere('company_id', 'default');
                })
                    ->paginate(9);
            }

            return response()->json([
                'success' => true,
                'message' => __('Games fetched successfully'),
                'data' => $games
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function duplicate(Request $request)
    {
        try {
            if (!$request->route('id')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Training ID is required')
                ], 422);
            }
            $id = base64_decode($request->route('id'));

            $trainingModule = TrainingModule::find($id);
            if (!$trainingModule) {
                return response()->json([
                    'success' => false,
                    'message' => __('Training Module not found')
                ], 422);
            }

            $originalPath = ltrim($trainingModule->cover_image, '/');
            $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
            $randomName = generateRandom(32) . '.' . $extension;
            $newPath = 'uploads/trainingModule/' . $randomName;

            // Check if file exists in S3
            if (!Storage::disk('s3')->exists($originalPath)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Original HTML file not found')
                ], 404);
            }

            // Get file content as string
            $fullPath = env('CLOUDFRONT_URL') . '/' . $originalPath;

            $fileContent = file_get_contents($fullPath);

            if (empty($fileContent)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to read original HTML file')
                ], 500);
            }

            // Save the copied file to S3
            Storage::disk('s3')->put($newPath, $fileContent);

            $duplicateTraining = $trainingModule->replicate(['company_id', 'name', 'cover_image']);
            $duplicateTraining->company_id = Auth::user()->company_id;
            $duplicateTraining->name = $trainingModule->name . ' (Copy)';
            $duplicateTraining->cover_image = '/' . $newPath;

            $duplicateTraining->save();

            return response()->json([
                'success' => true,
                'message' => __('Training Module duplicated successfully')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
