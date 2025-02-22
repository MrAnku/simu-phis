<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAgentRequest;
use App\Models\AiCallAgent;
use Illuminate\Http\Request;

class AiVishingController extends Controller
{
    public function index(){

        $all_requests = AiAgentRequest::with('company')->get();
        return view('admin.ai_vishing', compact('all_requests'));
    }   

    public function getPrompt($id){
        $id = base64_decode($id);
        $request = AiAgentRequest::with('company')->find($id);
        return response()->json($request);
    }

    public function approveAgent(Request $request){
        $request->validate([
            'agent_id' => 'required',
            'agent_name' => 'required',
            'request_id' => 'required'
        ]);

        $id = $request->request_id;
        $requested_agent = AiAgentRequest::find($id);

        if(!$requested_agent){
            return redirect()->back()->with('error', 'Agent Request Not Found');
        }

        AiCallAgent::create([
            'company_id' => $requested_agent->company_id,
            'agent_id' => $request->agent_id,
            'agent_name' => $request->agent_name
        ]);
        $requested_agent->status = 1;
        $requested_agent->save();

        return redirect()->back()->with('success', 'Agent Approved Successfully');
    }
}
