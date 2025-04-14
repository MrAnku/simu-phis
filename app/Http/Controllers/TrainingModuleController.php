<?php

namespace App\Http\Controllers;

use App\Models\TrainingGame;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TrainingModuleController extends Controller
{
    //

    public function allTrainingModule(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 0, 'msg' => 'Unauthorized'], 401);
        }

        // Correct: use get() instead of all()
        $all_training_module = TrainingModule::->get();

        return response()->json([
            'status' => 1,
            'all_training_module' => $all_training_module,
        ]);
    }


    public function index(Request $request)
    {
        $company_id = auth()->user()->company_id;

        if ($request->has('type') || $request->has('category')) {
            $selectedType = $request->input('type');
            $selectedCategory = $request->input('category');

            if ($selectedType == 'games') {

                $trainings = TrainingGame::where(function ($query) use ($company_id) {
                    $query->where('company_id', $company_id)
                        ->orWhere('company_id', 'default');
                })->paginate(10);
            } else {
                $trainings = TrainingModule::where('training_type', $selectedType)
                    ->where('category', $selectedCategory)
                    ->where(function ($query) use ($company_id) {
                        $query->where('company_id', $company_id)
                            ->orWhere('company_id', 'default');
                    })->paginate(10);
            }
        } else {
            $trainings = TrainingModule::where(function ($query) use ($company_id) {
                $query->where('company_id', $company_id)
                    ->orWhere('company_id', 'default');
            })->where('training_type', 'static_training')
                ->where('category', 'international')
                ->paginate(10);
        }

        $trainings->appends($request->except('page'));

        return view('trainingModules', compact('trainings'));
    }


    public function addTraining(Request $request)
    {
        $request->validate([
            'moduleName' => 'required|string|max:255',
            'mPassingScore' => 'required|numeric|min:0|max:100',
            'mCompTime' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'jsonData' => 'required|json',
            'mCoverFile' => 'nullable|file|mimes:jpg,jpeg,png',
            'mModuleLang' => 'nullable|string|max:5',
        ]);

        $moduleName = $request->input('moduleName');
        $mPassingScore = $request->input('mPassingScore');
        $mModuleLang = $request->input('mModuleLang', 'en');
        $filePath = 'uploads/trainingModule/defaultTraining.jpg';
        $mCompTime = $request->input('mCompTime');
        $jsonData = $request->input('jsonData');

        $companyId = auth()->user()->company_id;

        // Handling cover file
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
            'estimated_time' => $mCompTime,
            'cover_image' => "/" . $filePath,
            'passing_score' => $mPassingScore,
            'category' => $request->input('category'),
            'json_quiz' => $jsonData,
            'module_language' => $mModuleLang,
            'company_id' => $companyId,
        ]);

        if ($trainingModule->save()) {
            log_action("New training added {$moduleName}");
            return redirect()->back()->with('success', __('Training Added Successfully'));
        } else {
            log_action("Failed to add Training");
            return redirect()->back()->with('error', __('Failed to add Training'));
        }
    }

    public function addGamifiedTraining(Request $request)
    {
        $request->validate([
            'module_name' => 'required|string|max:255',
            'passing_score' => 'required|numeric|min:0|max:100',
            'completion_time' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'gamifiedJsonData' => 'required|json',
            'cover_file' => 'nullable|file|mimes:jpg,jpeg,png'
        ]);

        $companyId = auth()->user()->company_id;

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
            'name' => $request->module_name,
            'estimated_time' => $request->completion_time,
            'cover_image' => "/" . $filePath,
            'passing_score' => $request->passing_score,
            'category' => $request->category,
            'training_type' => 'gamified',
            'json_quiz' => $request->gamifiedJsonData,
            'module_language' => 'en',
            'company_id' => $companyId,
        ]);

        if ($trainingModule->save()) {
            log_action("New gamified training added {$request->module_name}");
            return redirect()->back()->with('success', __('Gamified training added successfully'));
        } else {
            log_action("Failed to add Training");
            return redirect()->back()->with('error', __('Failed to add gamified training'));
        }
    }

    public function updateGamifiedTraining(Request $request)
    {
        $request->validate([
            'module_name' => 'required|string|max:255',
            'passing_score' => 'required|numeric|min:0|max:100',
            'completion_time' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'gamifiedJsonData' => 'required|json',
            'gamifiedTrainingId' => 'required|numeric',
            'cover_file' => 'nullable|file|mimes:jpg,jpeg,png'
        ]);

        $company_id = auth()->user()->company_id;

        // handling cover file
        if ($request->hasFile('cover_file')) {
            $file = $request->file('cover_file');

            // Generate a random name for the file
            $randomName = generateRandom(32);
            $extension = $file->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;

            $filePath = $request->file('cover_file')->storeAs('/uploads/trainingModule', $newFilename, 's3');

            $isTrainingUpdated = TrainingModule::where('id', $request->gamifiedTrainingId)
                ->where('company_id', $company_id)
                ->update([
                    'name' => $request->module_name,
                    'estimated_time' => $request->completion_time,
                    'cover_image' => "/" . $filePath,
                    'passing_score' => $request->passing_score,
                    'category' => $request->input('category'),
                    'json_quiz' => $request->gamifiedJsonData,
                    'module_language' => 'en',
                ]);
        } else {
            $isTrainingUpdated = TrainingModule::where('id', $request->gamifiedTrainingId)
                ->where('company_id', $company_id)
                ->update([
                    'name' => $request->input('module_name'),
                    'estimated_time' => $request->input('completion_time'),
                    'passing_score' => $request->input('passing_score'),
                    'category' => $request->input('category'),
                    'json_quiz' => $request->input('gamifiedJsonData'),
                    'module_language' => 'en',
                ]);
        }

        if ($isTrainingUpdated) {
            log_action("Gamified training module updated");
            return redirect()->back()->with('success', __('Gamified training updated successfully'));
        } else {
            log_action("Failed to update Gamified Training");
            return redirect()->back()->with('error', __('Failed to update Gamified Training'));
        }
    }

    public function getTrainingById($id)
    {
        $trainingData = TrainingModule::find($id);

        if ($trainingData) {
            return response()->json($trainingData);
        } else {
            return response()->json(['error' => __('Training Module not found')], 404);
        }
    }

    public function getTrainingByType($type)
    {
        $company_id = auth()->user()->company_id;

        $trainings = TrainingModule::where('training_type', $type)
            ->where(function ($query) use ($company_id) {
                $query->where('company_id', $company_id)
                    ->orWhere('company_id', 'default');
            })->get();

        $internationalTrainings = $trainings->where('category', 'international');
        $middleEastTrainings = $trainings->where('category', 'middle_east');

        $res = [
            'international' => $internationalTrainings,
            'middle_east' => $middleEastTrainings
        ];

        return response()->json($res);
    }

    public function updateTrainingModule(Request $request)
    {
        // return "kk";
        $request->validate([
            'trainingModuleid' => 'required|integer|exists:training_modules,id',
            'moduleName' => 'required|string|max:255',
            'mPassingScore' => 'required|numeric',
            'category' => 'nullable|string|max:255',
            'mModuleLang' => 'nullable|string|max:255',
            'mCompTime' => 'required|numeric',
            'updatedjsonData' => 'required|json',
            'mCoverFile' => 'nullable|file|mimes:jpg,jpeg,png',
        ]);

        $trainingModuleId = $request->input('trainingModuleid');
        $moduleName = $request->input('moduleName');
        $mPassingScore = $request->input('mPassingScore');
        $mModuleLang = $request->input('mModuleLang') ?? 'en';
        $filePath = 'uploads/trainingModule/defaultTraining.jpg';
        $mCompTime = $request->input('mCompTime');
        $jsonData = $request->input('updatedjsonData');

        $company_id = auth()->user()->company_id;

        // handling cover file
        if ($request->hasFile('mCoverFile')) {

            $file = $request->file('mCoverFile');

            // Generate a random name for the file
            $randomName = generateRandom(32);
            $extension = $file->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;

            $filePath = $request->file('mCoverFile')->storeAs('/uploads/trainingModule', $newFilename, 's3');

            $isTrainingUpdated = TrainingModule::where('id', $trainingModuleId)
                ->where('company_id', $company_id)
                ->update([
                    'name' => $moduleName,
                    'estimated_time' => $mCompTime,
                    'cover_image' => "/" . $filePath,
                    'passing_score' => $mPassingScore,
                    'category' => $request->input('category'),
                    'json_quiz' => $jsonData,
                    'module_language' => $mModuleLang,
                ]);
        } else {
            $isTrainingUpdated = TrainingModule::where('id', $trainingModuleId)
                ->where('company_id', $company_id)
                ->update([
                    'name' => $moduleName,
                    'estimated_time' => $mCompTime,
                    'passing_score' => $mPassingScore,
                    'category' => $request->input('category'),
                    'json_quiz' => $jsonData,
                    'module_language' => $mModuleLang,
                ]);
        }

        if ($isTrainingUpdated) {
            log_action("Training module updated");
            return redirect()->back()->with('success', __('Training updated successfully'));
        } else {
            log_action("Failed to update Training");
            return redirect()->back()->with('error', __('Failed to update Training'));
        }
    }

    public function deleteTraining(Request $request)
    {
        $request->validate([
            'trainingid' => 'required|integer|exists:training_modules,id',
            'cover_image' => 'nullable|string',
        ]);

        $trainingId = $request->input('trainingid');
        $coverImage = $request->input('cover_image');
        $company_id = auth()->user()->company_id;

        // Deleting from reports
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

        DB::table('training_assigned_users')->where('training', $trainingId)->where('company_id', $company_id)->delete();
        $trainingModule = TrainingModule::where('id', $trainingId)->where('company_id', $company_id)->first();
        $isDeletedFromTrainingModules = $trainingModule->delete();

        if ($isDeletedFromTrainingModules && $coverImage != 'defaultTraining.jpg') {

            // Delete the file from S3
            Storage::disk('s3')->delete($trainingModule->cover_image);
        }

        if ($isDeletedFromTrainingModules) {
            log_action("Training module deleted");
            return redirect()->back()->with('success', __('Training deleted successfully'));
        } else {
            log_action("Failed to delete training module");
            return redirect()->back()->with('error', __('Failed to delete Training'));
        }
    }

    public function trainingPreview($trainingid)
    {
        $training = TrainingModule::find(base64_decode($trainingid));

        if (!$training) {
            return redirect()->back()->with('error', __('Invalid Training Module'));
        }

        if ($training->training_type == 'gamified') {
            return view('previewGamifiedTraining', compact('training'));
        }

        // Pass data to the view
        return view('previewTraining', ['trainingid' => $trainingid]);
    }

    public function loadPreviewTrainingContent($trainingid, $lang)
    {
        // Decode the ID
        $id = base64_decode($trainingid);

        // Validate the ID
        if ($id === false || !ctype_digit($id)) {
            return response()->json(['status' => 0, 'msg' => __('Invalid training module ID.')]);
        }

        // Fetch the training data
        $trainingData = TrainingModule::find($id);

        // Check if the training module exists
        if (!$trainingData) {
            return response()->json(['status' => 0, 'msg' => __('Training Module Not Found')]);
        }

        if ($trainingData->training_type == 'static_training') {

            // Access the module_language attribute
            $moduleLanguage = $lang;

            // You can now use $moduleLanguage as needed
            if ($moduleLanguage !== 'en') {

                $jsonQuiz = json_decode($trainingData->json_quiz, true);

                // $translatedArray = translateArrayValues($jsonQuiz, $moduleLanguage);
                // $translatedJson_quiz = json_encode($translatedArray, JSON_UNESCAPED_UNICODE);
                // var_dump($translatedArray);

                // $trainingData->json_quiz = $translatedJson_quiz;

                $translatedJson_quiz = translateQuizUsingAi($trainingData->json_quiz, $moduleLanguage);

                $translatedJson_quiz = json_decode($translatedJson_quiz, true);
                $translatedJson_quiz = changeTranslatedQuizVideoUrl($translatedJson_quiz, $moduleLanguage);

                $trainingData->json_quiz = json_encode($translatedJson_quiz, JSON_UNESCAPED_UNICODE);
                // var_dump($trainingData);
                // echo json_encode($trainingData, JSON_UNESCAPED_UNICODE);
            }

            // Pass data to the view
            return response()->json(['status' => 1, 'jsonData' => $trainingData]);
        }

        if ($trainingData->training_type == 'gamified') {
            $moduleLanguage = $lang;

            if ($moduleLanguage !== 'en') {
                $quizInArray = json_decode($trainingData->json_quiz, true);
                $quizInArray['videoUrl'] = changeVideoLanguage($quizInArray['videoUrl'], $moduleLanguage);
                return $this->translateJsonData($quizInArray, $moduleLanguage);
            }

            return response()->json(['status' => 1, 'jsonData' => $trainingData->json_quiz]);
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
                    'status' => 0,
                    'msg' => $response->body(),
                ]);
            }

            $translatedJson = $response['choices'][0]['message']['content'];


            return response()->json([
                'status' => 1,
                'jsonData' => json_decode($translatedJson, true),
            ]);
        } catch (\Exception $e) {


            return response()->json([
                'status' => 0,
                'msg' => $e->getMessage(),
            ]);
        }
    }
}
