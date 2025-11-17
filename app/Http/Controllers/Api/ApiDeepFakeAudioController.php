<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\DeepFakeAudio;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiDeepFakeAudioController extends Controller
{
    public function deepFakeAudios(){
        $audios = DeepFakeAudio::where('company_id', 'default')
        ->orWhere('company_id', Auth::user()->company_id)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $audios,
        ]);
    }
}
