<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class AdminTrainingModuleController extends Controller
{
   //
   public function index()
   {

       $trainingModules = TrainingModule::all();
       $interTrainings = $trainingModules->where('category', 'international');
        $middleEastTrainings = $trainingModules->where('category', 'middle_east');
       return view('admin.trainingModules', compact('interTrainings', 'middleEastTrainings'));
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
       $mCoverFile = 'defaultTraining.jpg';
       $mCompTime = $request->input('mCompTime');
       $jsonData = $request->input('jsonData');


       // Handling cover file
       if ($request->hasFile('mCoverFile')) {
           $file = $request->file('mCoverFile');
           $mCoverFile = generateRandom(32) . '.' . $file->getClientOriginalExtension();
           $file->storeAs('public/uploads/trainingModule', $mCoverFile);
       }

       $trainingModule = new TrainingModule([
           'name' => $moduleName,
           'estimated_time' => $mCompTime,
           'cover_image' => $mCoverFile,
           'passing_score' => $mPassingScore,
           'category' => $request->input('category'),
           'json_quiz' => $jsonData,
           'module_language' => $mModuleLang,
           'company_id' => 'default',
       ]);

       if ($trainingModule->save()) {
           return redirect()->back()->with('success', 'Training Added Successfully');
       } else {
           return redirect()->back()->with('error', 'Failed to add Training');
       }
   }

   public function getTrainingById($id)
   {
       $trainingData = TrainingModule::find($id);

       if ($trainingData) {
           return response()->json($trainingData);
       } else {
           return response()->json(['error' => 'Training Module not found'], 404);
       }
   }

   public function updateTrainingModule(Request $request)
   {
       $request->validate([
           'trainingModuleid' => 'required|integer|exists:training_modules,id',
           'moduleName' => 'required|string|max:255',
           'mPassingScore' => 'required|numeric',
           'mModuleLang' => 'nullable|string|max:255',
           'mCompTime' => 'required|numeric',
           'updatedjsonData' => 'required|json',
           'mCoverFile' => 'nullable|file|mimes:jpg,jpeg,png',
       ]);

       $trainingModuleId = $request->input('trainingModuleid');
       $moduleName = $request->input('moduleName');
       $mPassingScore = $request->input('mPassingScore');
       $mModuleLang = $request->input('mModuleLang') ?? 'en';
       $mCoverFile = 'defaultTraining.jpg';
       $mCompTime = $request->input('mCompTime');
       $jsonData = $request->input('updatedjsonData');


       // handling cover file
       if ($request->hasFile('mCoverFile')) {
           $file = $request->file('mCoverFile');
           $randomName = generateRandom(32);
           $extension = $file->getClientOriginalExtension();
           $mCoverFile = $randomName . '.' . $extension;
           $file->storeAs('uploads/trainingModule', $mCoverFile, 'public');

           $isTrainingUpdated = TrainingModule::where('id', $trainingModuleId)
               ->update([
                   'name' => $moduleName,
                   'estimated_time' => $mCompTime,
                   'cover_image' => $mCoverFile,
                   'passing_score' => $mPassingScore,
                   'category' => $request->input('category'),
                   'json_quiz' => $jsonData,
                   'module_language' => $mModuleLang,
               ]);
       } else {
           $isTrainingUpdated = TrainingModule::where('id', $trainingModuleId)
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
           return redirect()->back()->with('success', 'Training updated successfully');
       } else {
           return redirect()->back()->with('error', 'Failed to update Training');
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

       // Deleting from reports
       $campaigns = DB::table('all_campaigns')
           ->where('training_module', $trainingId)
           ->get();

       if ($campaigns->count() > 0) {
           $campIdArray = $campaigns->pluck('campaign_id');

           foreach ($campIdArray as $campId) {
               DB::table('all_campaigns')->where('campaign_id', $campId)->delete();
               DB::table('campaign_live')->where('campaign_id', $campId)->delete();
               DB::table('campaign_reports')->where('campaign_id', $campId)->delete();
           }
       }

       DB::table('training_assigned_users')->where('training', $trainingId)->delete();
       $isDeletedFromTrainingModules = TrainingModule::where('id', $trainingId)->delete();

       if ($isDeletedFromTrainingModules && $coverImage != 'defaultTraining.jpg') {
           $coverFile = 'uploads/trainingModule/' . $coverImage;
           if (Storage::exists($coverFile)) {
               Storage::delete($coverFile);
           }
       }

       if ($isDeletedFromTrainingModules) {
           return redirect()->back()->with('success', 'Training deleted successfully');
       } else {
           return redirect()->back()->with('error', 'Failed to delete Training');
       }
   }

   public function trainingPreview($trainingid)
   {
      
       // Pass data to the view
       return view('admin.previewTraining', ['trainingid'=>$trainingid]);
   }

   public function loadPreviewTrainingContent($trainingid)
   {
       // Decode the ID
       $id = base64_decode($trainingid);

       // Validate the ID
       if ($id === false || !ctype_digit($id)) {
           return response()->json(['status'=> 0, 'msg' => 'Invalid training module ID.']);
       }

       // Fetch the training data
       $trainingData = TrainingModule::find($id);

       // Check if the training module exists
       if (!$trainingData) {
           return response()->json(['status'=> 0, 'msg' => 'Training Module Not Found']);
       }

       // Access the module_language attribute
       $moduleLanguage = $trainingData->module_language;

       // You can now use $moduleLanguage as needed
       if ($moduleLanguage !== 'en') {

           $jsonQuiz = json_decode($trainingData->json_quiz, true);

           $translatedArray = translateArrayValues($jsonQuiz, $moduleLanguage);
           $translatedJson_quiz = json_encode($translatedArray, JSON_UNESCAPED_UNICODE);
           // var_dump($translatedArray);

           $trainingData->json_quiz = $translatedJson_quiz;
           // var_dump($trainingData);
           // echo json_encode($trainingData, JSON_UNESCAPED_UNICODE);
       }

       // Pass data to the view
       return response()->json(['status'=> 1, 'jsonData' => $trainingData]);
   }
}
