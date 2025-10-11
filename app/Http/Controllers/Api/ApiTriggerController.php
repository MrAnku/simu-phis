<?php

namespace App\Http\Controllers\Api;

use App\Models\Policy;
use Illuminate\Http\Request;
use App\Models\CompanyTrigger;
use App\Models\TrainingModule;
use App\Http\Controllers\Controller;
use App\Models\ScormTraining;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiTriggerController extends Controller
{
    public function index()
    {
        try {

            $trainingModules = TrainingModule::where('company_id', Auth::user()->company_id)
                ->orWhere('company_id', 'default')
                ->get(['id', 'name', 'company_id', 'training_type']);
            $policies = Policy::where('company_id', Auth::user()->company_id)
                ->get(['id', 'policy_name', 'company_id']);
            $scorms = ScormTraining::where('company_id', Auth::user()->company_id)->get(['id', 'name', 'company_id']);

            $triggers = CompanyTrigger::where('company_id', Auth::user()->company_id)->get();
            return response()->json([
                'success' => true,
                'data' => [
                    'training_modules' => $trainingModules,
                    'policies' => $policies,
                    'scorms' => $scorms,
                    'triggers' => $triggers
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function addTrigger(Request $request)
    {


        try {
            $request->validate([
                'event_type' => 'required|in:new_user',
                'employee_type' => 'required|in:bluecollar,normal',
                'training' => 'nullable|array',
                'policy' => 'nullable|array',
                'scorm' => 'nullable|array',
            ]);
            //check if company already has this trigger
            $exists = CompanyTrigger::where('event_type', $request->event_type)
                ->where('employee_type', $request->employee_type)
                ->where('company_id', Auth::user()->company_id)
                ->exists();

            if ($exists) {
                return response()->json(['success' => false, 'message' => __('Trigger event already exists. You can update it instead.')], 409);
            }
            if (!$request->training && !$request->policy && !$request->scorm) {
                return response()->json(['success' => false, 'message' => __('At least one trigger type (training, policy, scorm) must be provided.')], 400);
            }

            $trigger = new CompanyTrigger();
            $trigger->event_type = $request->event_type;
            $trigger->employee_type = $request->employee_type;
            $trigger->training = $request->training ? json_encode($request->training) : null;
            $trigger->policy = $request->policy ? json_encode($request->policy) : null;
            $trigger->scorm = $request->scorm ? json_encode($request->scorm) : null;
            $trigger->status = 1; // Active by default
            $trigger->company_id = Auth::user()->company_id;
            $trigger->save();

            return response()->json(['success' => true, 'message' => __('Trigger added successfully')], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Validation Error: ') .$e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function updateTrigger(Request $request, $id)
    {


        try {
            $request->validate([
                'event_type' => 'required|in:new_user',
                'employee_type' => 'required|in:bluecollar,normal',
                'training' => 'nullable|array',
                'policy' => 'nullable|array',
                'scorm' => 'nullable|array',
                'status' => 'required|in:0,1',
            ]);
            //check if this employee type already exists for this company
            $employeeTypeExists = CompanyTrigger::where('employee_type', $request->employee_type)
                ->where('company_id', Auth::user()->company_id)
                ->exists();
            if($employeeTypeExists){
                return response()->json(['success' => false, 'message' => __('This employee type trigger already exists. You can update it instead.')], 409);
            }


            $trigger = CompanyTrigger::where('id', base64_decode($id))
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$trigger) {
                return response()->json(['success' => false, 'message' => __('Trigger not found')], 404);
            }

            if ($request->has('event_type')) {
                $trigger->event_type = $request->event_type;
                $trigger->employee_type = $request->employee_type;
            }
            if ($request->has('training')) {
                $trigger->training = $request->training ? json_encode($request->training) : null;
            }
            if ($request->has('policy')) {
                $trigger->policy = $request->policy ? json_encode($request->policy) : null;
            }
            if ($request->has('scorm')) {
                $trigger->scorm = $request->scorm ? json_encode($request->scorm) : null;
            }
            if ($request->has('status')) {
                $trigger->status = $request->status;
            }

            $trigger->save();

            return response()->json(['success' => true, 'message' => __('Trigger updated successfully')], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => __('Validation Error: ') .$e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $trigger = CompanyTrigger::where('id', base64_decode($id))
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$trigger) {
                return response()->json(['success' => false, 'message' => __('Trigger not found')], 404);
            }

            $trigger->status = !$trigger->status;
            $trigger->save();

            return response()->json(['success' => true, 'message' => __('Trigger updated')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function deleteTrigger($id)
    {
        try {
            $trigger = CompanyTrigger::where('id', base64_decode($id))
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$trigger) {
                return response()->json(['success' => false, 'message' => __('Trigger not found')], 404);
            }

            $trigger->delete();

            return response()->json(['success' => true, 'message' => __('Trigger deleted successfully')], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
}
