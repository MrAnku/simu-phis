<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use App\Models\TrainingGame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminTrainingGameController extends Controller
{
    public function index()
    {
        $data = TrainingGame::orderBy('created_at', 'desc')->get();

        return view('admin.training_game', compact('data'));
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 
                Rule::unique('training_games', 'slug'),
            ],
            'cover_image' => 'nullable|max:5125',
        ]);

        if ($request->hasFile('cover_image')) {
            $cover_image = $request->file('cover_image');
            $fileUrl = generateRandom(32) . '.' . $cover_image->getClientOriginalExtension();
            $cover_image->storeAs('public/uploads/trainingGame', $fileUrl);
        } else {
            $fileUrl = null;
        }
        
        TrainingGame::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'company_id' => 'default',
            'cover_image' => $fileUrl ?? null
        ]);
        return redirect()->back()->with('success', 'Data added successfully');
    }

    public function deleteData(Request $request){
        $id = $request->id;
        $data = TrainingGame::find($id);
        if ($data->cover_image) {
            $filePath = public_path('storage/uploads/trainingGame/' . $data->cover_image);  
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        $data->delete();
        return response()->json(['status' => 1, 'msg' => 'Data deleted successfully']);
   }

    public function gameScore(Request $request){
        $assignedUserId = base64_decode($request->assignedUserId);
        $assignedUser = TrainingAssignedUser::where('id', $assignedUserId)->first();
        if($assignedUser->personal_best < $request->score){
            $assignedUser->personal_best = $request->score;
        }
        $assignedUser->game_time = $request->timeConsumed;
        $assignedUser->save();
        return response()->json(['status' => true, 'data' => [
            'score' => $assignedUser->personal_best,
            'timeConsumed' => $assignedUser->game_time
        ]]);
    }
}
