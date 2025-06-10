<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TrainingGame;
use App\Models\TrainingModule;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiTrainingModuleController extends Controller
{

    public function allTrainingModule(Request $request)
    {
        try {
            $user = Auth::user();
            $companyId = Auth::user()->company_id;

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // Default 10 items per page
            $perPage = 10;

            // Get paginated training modules
            $trainingModules = TrainingModule::where('company_id', 'default')->orWhere('company_id', $companyId)->get();

            // $trainingModules = TrainingModule::paginate($perPage);


            return response()->json([
                'success' => true,
                'all_training_module' => $trainingModules,
                'message' => __('Fetch All Training Module')
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
            'mCompTime' => 'required|string|max:255',
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
            $mCompTime = $request->input('mCompTime');
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
                'estimated_time' => $mCompTime,
                'cover_image' => "/" . $filePath,
                'passing_score' => $mPassingScore,
                'json_quiz' => $jsonData,
                'module_language' => $mModuleLang,
                'company_id' => $companyId,
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
            $cover_file = 'defaultTraining.jpg';

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

            // Handle cover image if provided
            if ($request->hasFile('cover_file')) {

                $file = $request->file('cover_file');

                // Generate a random name for the file
                $randomName = generateRandom(32);
                $extension = $file->getClientOriginalExtension();
                $newFilename = $randomName . '.' . $extension;

                $filePath = $request->file('cover_file')->storeAs('/uploads/trainingModule', $newFilename, 's3');
                $updateData['cover_image'] = $newFilename;
            }

            $isTrainingUpdated = TrainingModule::where('id', $request->input('gamifiedTrainingId'))
                ->where('company_id', $company_id)
                ->update($updateData);

            if ($isTrainingUpdated) {
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
            'mCompTime' => 'required|numeric',
            'updatedjsonData' => 'required|json',
            // 'mCoverFile' => 'nullable|file|mimes:jpg,jpeg,png',
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
        $mCompTime = $request->input('mCompTime');
        $jsonData = $request->input('updatedjsonData');
        $programResources = $request->input('program_resources') === 'null' ? null : $request->input('program_resources');
        $company_id = Auth::user()->company_id;

        try {
            $updateData = [
                'name' => $moduleName,
                'estimated_time' => $mCompTime,
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
            ];
            // return $request->file('mCoverFile');
            if ($request->hasFile('mCoverFile')) {
                $file = $request->file('mCoverFile');

                // Generate a random name for the file
                $randomName = generateRandom(32);
                $extension = $file->getClientOriginalExtension();
                $newFilename = $randomName . '.' . $extension;

                $filePath = $request->file('mCoverFile')->storeAs('/uploads/trainingModule', $newFilename, 's3');
                $updateData['cover_image'] = $newFilename;
            }
            // return $trainingModuleId;
            // $updateData['cover_image'] = "default image";

            $isTrainingUpdated = TrainingModule::where('id', $trainingModuleId)->update($updateData);

            if ($isTrainingUpdated) {
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
            // Start DB transaction
            DB::beginTransaction();

            // Deleting related campaign data
            $campaigns = DB::table('all_campaigns')
                ->where('training_module', $trainingId)
                ->where('company_id', $company_id)
                ->get();

            if ($campaigns->count() > 0) {
                $campIdArray = $campaigns->pluck('campaign_id');

                foreach ($campIdArray as $campId) {
                    DB::table('all_campaigns')->where('campaign_id', $campId)->where('company_id', $company_id)->delete();
                    DB::table('campaign_live')->where('campaign_id', $campId)->where('company_id', $company_id)->delete();
                    DB::table('campaign_reports')->where('campaign_id', $campId)->where('company_id', $company_id)->delete();
                }
            }

            // Delete assigned users
            DB::table('training_assigned_users')->where('training', $trainingId)->where('company_id', $company_id)->delete();

            // Delete training module
            $trainingModule = TrainingModule::where('id', $trainingId)->where('company_id', $company_id)->first();

            $isDeleted  = $trainingModule->delete();

            // Delete cover image if not default
            if ($isDeleted && $coverImage !== 'defaultTraining.jpg') {
                // Delete the file from S3
                Storage::disk('s3')->delete($trainingModule->cover_image);
            }

            DB::commit(); // Commit transaction
            log_action("Training module deleted");

            return response()->json([
                'success' => true,
                'message' => __('Training deleted successfully'),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack(); // Rollback on error
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
                    $translatedJson_quiz = translateQuizUsingAi($trainingData->json_quiz, $moduleLanguage);
                    $translatedJson_quiz = json_decode($translatedJson_quiz, true);
                    $translatedJson_quiz = changeTranslatedQuizVideoUrl($translatedJson_quiz, $moduleLanguage);

                    $trainingData->json_quiz = json_encode($translatedJson_quiz, JSON_UNESCAPED_UNICODE);
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
                    $quizInArray = json_decode($trainingData->json_quiz, true);
                    $quizInArray['videoUrl'] = changeVideoLanguage($quizInArray['videoUrl'], $moduleLanguage);

                    // Assuming `translateJsonData` already returns a JSON response
                    return $this->translateJsonData($quizInArray, $moduleLanguage);
                }

                return response()->json([
                    'success' => false,
                    'jsonData' => $trainingData->json_quiz,
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

            $games = TrainingGame::where('company_id', $companyId)
                ->orWhere('company_id', 'default')
                ->get()
                ->map(function ($game) {
                    $game->cover_image = 'storage/uploads/trainingGame/' . $game->cover_image;
                    return $game;
                });

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

             $trainingModuleExists = TrainingModule::where('id', $id)->where('company_id', '!=', 'default')->first();
             if($trainingModuleExists){
                return response()->json([
                    'success' => false,
                    'message' => __('Training Module already exists for this company')
                ], 422);
             }

            $trainingModule = TrainingModule::where('id', $id)->where('company_id', 'default')->first();
            if (!$trainingModule) {
                return response()->json([
                    'success' => false,
                    'message' => __('Training Module not found')
                ], 422);
            }

            $duplicateTraining = $trainingModule->replicate(['company_id', 'name']);
            $duplicateTraining->company_id = Auth::user()->company_id;
            $duplicateTraining->name = $trainingModule->name . ' (Copy)';

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
