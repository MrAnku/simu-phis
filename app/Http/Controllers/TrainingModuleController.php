<?php

namespace App\Http\Controllers;

use App\Models\TrainingModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrainingModuleController extends Controller
{
    //
    public function index()
    {
        $company_id = auth()->user()->company_id;

        $trainingModules = TrainingModule::where('company_id', $company_id)
            ->orWhere('company_id', 'default')
            ->get();
        return view('trainingModules', compact('trainingModules'));
    }

    public function addTraining(Request $request)
    {
        $request->validate([
            'moduleName' => 'required|string|max:255',
            'mPassingScore' => 'required|numeric|min:0|max:100',
            'mCompTime' => 'required|string|max:255',
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

        $companyId = auth()->user()->company_id;

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
            'json_quiz' => $jsonData,
            'module_language' => $mModuleLang,
            'company_id' => $companyId,
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

        $company_id = auth()->user()->company_id;

        // handling cover file
        if ($request->hasFile('mCoverFile')) {
            $file = $request->file('mCoverFile');
            $randomName = generateRandom(32);
            $extension = $file->getClientOriginalExtension();
            $mCoverFile = $randomName . '.' . $extension;
            $file->storeAs('uploads/trainingModule', $mCoverFile, 'public');

            $isTrainingUpdated = TrainingModule::where('id', $trainingModuleId)
                ->where('company_id', $company_id)
                ->update([
                    'name' => $moduleName,
                    'estimated_time' => $mCompTime,
                    'cover_image' => $mCoverFile,
                    'passing_score' => $mPassingScore,
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
        $isDeletedFromTrainingModules = TrainingModule::where('id', $trainingId)->where('company_id', $company_id)->delete();

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
}
