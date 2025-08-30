<?php

namespace App\Http\Controllers\Api;

use App\Models\Policy;
use Illuminate\Http\Request;
use App\Models\CompanyTrigger;
use App\Models\TrainingModule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiTriggerController extends Controller
{
    public function index()
    {
        try {

            $trainingModules = TrainingModule::where('company_id', Auth::user()->company_id)
            ->orWhere('company_id', 'default')
            ->get(['id', 'name', 'company_id']);
            $policies = Policy::where('company_id', Auth::user()->company_id)
            ->get(['id', 'policy_name', 'company_id']);

            $triggers = CompanyTrigger::with(['training', 'policy'])->where('company_id', Auth::user()->company_id)->get();
            return response()->json([
                'success' => true, 
                'data' => [
                    'training_modules' => $trainingModules,
                    'policies' => $policies,
                    'triggers' => $triggers
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function addTrigger(Request $request)
    {
        $request->validate([
            'event_type' => 'required|in:new_user',
            'training' => 'nullable|integer|exists:training_modules,id',
            'policy' => 'nullable|integer|exists:policies,id',
        ]);

        try {
            //check if company already has this trigger
            $exists = CompanyTrigger::where('event_type', $request->event_type)
                ->where('company_id', Auth::user()->company_id)
                ->exists();

            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Trigger event already exists. You can update it instead.'], 409);
            }

            $trigger = new CompanyTrigger();
            $trigger->event_type = $request->event_type;
            $trigger->training = $request->training ?? null;
            $trigger->policy = $request->policy ?? null;
            $trigger->status = 1; // Active by default
            $trigger->company_id = Auth::user()->company_id;
            $trigger->save();

            return response()->json(['success' => true, 'message' => 'Trigger added successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateTrigger(Request $request, $id)
    {


        try {
            $request->validate([
                'event_type' => 'sometimes|required|in:new_user',
                'training' => 'sometimes|required|integer',
                'policy' => 'sometimes|required|integer',
                'status' => 'sometimes|required|in:0,1',
            ]);

            $trigger = CompanyTrigger::where('id', base64_decode($id))
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$trigger) {
                return response()->json(['success' => false, 'message' => 'Trigger not found'], 404);
            }

            if ($request->has('event_type')) {
                $trigger->event_type = $request->event_type;
            }
            if ($request->has('training')) {
                $trigger->training = $request->training;
            }
            if ($request->has('policy')) {
                $trigger->policy = $request->policy;
            }
            if ($request->has('status')) {
                $trigger->status = $request->status;
            }

            $trigger->save();

            return response()->json(['success' => true, 'message' => 'Trigger updated successfully'], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteTrigger($id)
    {
        try {
            $trigger = CompanyTrigger::where('id', base64_decode($id))
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$trigger) {
                return response()->json(['success' => false, 'message' => 'Trigger not found'], 404);
            }

            $trigger->delete();

            return response()->json(['success' => true, 'message' => 'Trigger deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
