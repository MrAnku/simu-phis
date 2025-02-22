<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAgentRequest;
use App\Models\AiCallAgent;
use App\Models\AiCallCampaign;
use App\Models\AiCallCampLive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class AiVishingController extends Controller
{
    public function index()
    {

        $all_requests = AiAgentRequest::with(['company', 'agent'])->get();
        $all_agents = AiCallAgent::all();
        return view('admin.ai_vishing', compact('all_requests', 'all_agents'));
    }

    public function getPrompt($id)
    {
        $id = base64_decode($id);
        $request = AiAgentRequest::with(['company', 'agent'])->find($id);
        return response()->json($request);
    }

    public function approveAgent(Request $request)
    {
        $request->validate([
            'agent_id' => 'required',
            'agent_name' => 'required',
            'request_id' => 'required'
        ]);

        $id = $request->request_id;
        $requested_agent = AiAgentRequest::find($id);

        if (!$requested_agent) {
            return redirect()->back()->with('error', 'Agent Request Not Found');
        }

        AiCallAgent::create([
            'company_id' => $requested_agent->company_id,
            'request_id' => $requested_agent->id,
            'agent_id' => $request->agent_id,
            'agent_name' => $request->agent_name
        ]);
        $requested_agent->status = 1;
        $requested_agent->save();

        return redirect()->back()->with('success', 'Agent Approved Successfully');
    }

    public function deleteAgentRequest(Request $request)
    {
        $id = base64_decode($request->id);
        $requested_agent = AiAgentRequest::find($id);

        if (!$requested_agent) {
            return response()->json(['error' => 'Agent Request Not Found']);
        }

        if($requested_agent->status == 1){
            $agent = AiCallAgent::where('request_id', $requested_agent->id)->first();
            if($agent){
                $agent_id = $agent->agent_id;
                AiCallCampLive::where('agent_id', $agent_id)->delete();
                AiCallCampaign::where('ai_agent', $agent_id)->delete();
            }
            $agent->delete();
        }
        

        if ($requested_agent->audio_file !== null) {
            $filePath = storage_path('app/public/deepfake_audio/' . $requested_agent->audio_file);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $requested_agent->delete();
        return response()->json(['success' => 'Agent Request Deleted Successfully']);
    }

    public function newAgent(Request $request)
    {
        $request->validate([
            'agent_id' => 'required',
            'agent_name' => 'required'
        ]);

        AiCallAgent::create([
            'company_id' => 'default',
            'agent_id' => $request->agent_id,
            'agent_name' => $request->agent_name
        ]);

        return redirect()->back()->with('success', 'Agent Added Successfully');
    }

    public function deleteAgent(Request $request)
    {

        $agent_id = base64_decode($request->agent_id);

        $agent = AiCallAgent::where('agent_id', $agent_id)->first();
        if (!$agent) {
            return response()->json(['error' => 'Agent Not Found']);
        }

        if($agent->request_id !== null){
            AiAgentRequest::where('id', $agent->request_id)->delete();
        }

        AiCallCampLive::where('agent_id', $agent_id)->delete();
        AiCallCampaign::where('ai_agent', $agent_id)->delete();

        $agent->delete();
        return response()->json(['success' => 'Agent Deleted Successfully']);
    }
}
