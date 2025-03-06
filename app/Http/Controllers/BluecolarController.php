<?php

namespace App\Http\Controllers;

use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarGroup;
use App\Models\BluecollarTrainingInitiator;
use App\Models\BlueCollarTrainingUser;
use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\CampaignReport;
use App\Models\DomainVerified;
use App\Models\OutlookAdToken;
use App\Models\TrainingAssignedUser;
use App\Models\TrainingModule;
use App\Models\UsersGroup;
use App\Models\WhatsappCampaign;
use App\Models\WhatsAppCampaignUser;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BluecolarController extends Controller
{
    public function BlueCollarIndex()
    {
        $companyId = Auth::user()->company_id;

        $groups = BlueCollarGroup::withCount('bluecollarusers')
            ->where('company_id', $companyId)
            ->get();

        $totalEmployees = BlueCollarEmployee::where('company_id', $companyId)->get()->count();
        $totalActiveEmployees = WhatsAppCampaignUser::where("employee_type", "Bluecollar")
            ->where('company_id', $companyId)
            ->get()
            ->count();
        $totalCompromisedEmployees = WhatsAppCampaignUser::where("employee_type", "Bluecollar")
            ->where("emp_compromised", 1)
            ->where('company_id', $companyId)
            ->get()
            ->count();

        $totalEmps = $groups->sum('bluecollarusers_count');

        $hasOutlookAdToken = OutlookAdToken::where('company_id', $companyId)->exists();

        return view('BlueCollars', compact(
            'groups',
            'totalEmps',
            'totalEmployees',
            'totalActiveEmployees',
            'totalCompromisedEmployees',
            'hasOutlookAdToken'
        ));
    }


    public function fetchGroup(Request $request)
    {
        $query = $request->data;

        if ($query == "Normal") {
            $result = UsersGroup::all();
        } else {
            $result = BlueCollarGroup::all();
        }

        return response()->json($result);
    }

    public function blueCollarNewGroup(Request $request)
    {
        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->with('error', 'Invalid input detected.');
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        $grpName = $request->input('usrGroupName');
        $grpId = generateRandom(6);
        $companyId = auth()->user()->company_id; // Assuming company_id is stored in the authenticated user

        BlueCollarGroup::create([
            'group_id' => $grpId,
            'group_name' => $grpName,
            'users' => null,
            'company_id' => $companyId,
        ]);

        log_action("New employee group {$grpName} created");

        return redirect()->route('bluecollar.employees');
    }

    public function addBlueCollarUser(Request $request)
    {
        //xss check start

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status' => 0, 'msg' => 'Invalid input detected.']);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

        $validator = Validator::make($request->all(), [
            'groupid' => 'required',
            'usrName' => 'required|string|max:255',
            'usrCompany' => 'nullable|string|max:255',
            'usrJobTitle' => 'nullable|string|max:255',
            'usrWhatsapp' => 'nullable|digits_between:11,15',
        ]);

        $request->merge([
            'usrWhatsapp' => preg_replace('/\D/', '', $request->usrWhatsapp)
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'msg' => $validator->errors()->first()]);
        }
        $companyId = Auth::user()->company_id;

        // Checking the limit of employees
        if (Auth::user()->usedemployees >= Auth::user()->employees) {
            log_action("Employee limit has exceeded");
            return response()->json(['status' => 0, 'msg' => 'Employee limit has been reached']);
        }

        //checking if the domain is verified
        // if (!$this->domainVerified($request->usrEmail, $companyId)) {
        //     return response()->json(['status' => 0, 'msg' => 'Domain is not verified']);
        // }

        //checking if the email is unique
        $user = BlueCollarEmployee::where('whatsapp', $request->usrWhatsapp)->exists();
        if ($user) {
            return response()->json(['status' => 0, 'msg' => 'This Whatsapp already exists / Or added by some other company']);
        }

        BlueCollarEmployee::create(
            [
                'group_id' => $request->groupid,
                'user_name' => $request->usrName,
                'user_company' => !empty($request->usrCompany) ? $request->usrCompany : null,
                'user_job_title' => !empty($request->usrJobTitle) ? $request->usrJobTitle : null,
                'whatsapp' => !empty($request->usrWhatsapp) ? $request->usrWhatsapp : null,
                'company_id' => $companyId,
            ]
        );
        Auth::user()->increment('usedemployees');

        return response()->json(['status' => 1, 'msg' => 'Employee Added Successfully']);
    }

    public function deleteBlueUser(Request $request)
    {
        $user = BlueCollarEmployee::find($request->user_id);
        // $ifBreached = BreachedEmail::where('email', $user->user_email)->delete();

        if ($user) {
            // $ifBreached = BreachedEmail::where('email', $user->user_email)->delete();
            // log_action("User {$user->user_email} deleted");
            $user->delete();

            return response()->json(['status' => 1, 'msg' => 'User deleted successfully'], 200);
        } else {

            log_action("User not found to delete");
            return response()->json(['status' => 0, 'msg' => 'User not found'], 404);
        }
    }
    public function viewBlueCollarUsers($groupid)
    {
        $companyId = auth()->user()->company_id;
        $users = BlueCollarEmployee::where('group_id', $groupid)->where('company_id', $companyId)->get();

        if (!$users->isEmpty()) {
            return response()->json(['status' => 1, 'data' => $users]);
        } else {
            return response()->json(['status' => 0, 'msg' => 'no employees found']);
        }
    }

    public function deleteBlueGroup(Request $request)
    {
        $grpId = $request->input('group_id');
        $companyId = Auth::user()->company_id;
        // return  $grpId;
        DB::beginTransaction();
        try {
            // Delete the group
            BlueCollarGroup::where('group_id', $grpId)
                ->where('company_id', $companyId)
                ->delete();

            // Find all users in the group
            $users = BlueCollarEmployee::where('group_id', $grpId)->get();

            if ($users->isNotEmpty()) {
                foreach ($users as $user) {
                    BlueCollarTrainingUser::where('user_id', $user->id)->delete();
                }
            }

            // Check if any campaigns are using this group
            $campaigns = WhatsappCampaign::where('user_group', $grpId)
                ->where('company_id', $companyId)
                ->get();


            if ($campaigns->isNotEmpty()) {
                foreach ($campaigns as $campaign) {
                    WhatsappCampaign::where('campaign_id', $campaign->campaign_id)
                        ->where('company_id', $companyId)
                        ->delete();

                    WhatsAppCampaignUser::where('campaign_id', $campaign->campaign_id)
                        ->where('company_id', $companyId)
                        ->delete();
                }
            }
            // return $users;
            // Delete employees in the group regardless of campaigns
            BlueCollarEmployee::where('group_id', $grpId)->delete();

            DB::commit();
            log_action("Employee group deleted");
            return response()->json(['status' => 1, 'msg' => 'Employee group deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            log_action("An error occurred while deleting the employee group");
            return response()->json(['status' => 0, 'msg' => 'An error occurred while deleting the employee group', 'error' => $e->getMessage()]);
        }
    }


    public function storeBlueCollarUser(Request $request)
    {
        try {
            $decodedId = base64_decode($request->encoded_id);

            $request->validate([
                'name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:15|unique:bluecollar_training_initiators,phone_number',
            ]);

            $TrainingData = BlueCollarTrainingUser::where('id', $decodedId)->first();
            if (!$TrainingData) {
                return response()->json(['success' => false, 'message' => 'Training data not found.'], 404);
            }

            BluecollarTrainingInitiator::create([
                'name' => $request->name,
                'phone_number' => $request->phone_number,
            ]);

            $assignedTraining = BlueCollarTrainingUser::with('trainingData')
                ->where('id', $decodedId)
                ->where('completed', 0)
                ->first();

            if ($assignedTraining && $assignedTraining->training_type == 'static_training') {
                $trainingUrl = route('learner.start.bluecollartraining', [
                    'training_id' => encrypt($assignedTraining->training),
                    'training_lang' => $assignedTraining->training_lang,
                    'id' => base64_encode($TrainingData->id),
                ]);

                return response()->json(['success' => true, 'trainingUrl' => $trainingUrl]);
            } else {

                return response()->json(['success' => true, 'message' => 'User created successfully.']);
            }
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function bluecollarStartTraining($training_id, $training_lang, $id)
    {
        log_action("Employee started static training", 'learner', 'learner');
        // $training_id = decrypt($training_id);

        return view('bluecollarlearning.bluecollartraining', ['trainingid' => $training_id, 'training_lang' => $training_lang, 'id' => $id]);
    }

    public function bluecollarUpdateTrainingScore(Request $request)
    {
        // Validate the request
        $request->validate([
            'trainingScore' => 'required|integer',
            'id' => 'required',
        ]);



        $row_id = base64_decode($request->id);

        // $rowData = TrainingAssignedUser::find($row_id);
        $rowData = BlueCollarTrainingUser::find($row_id);
        if ($rowData && $request->trainingScore > $rowData->personal_best) {
            // Update the column if the current value is greater
            $rowData->personal_best = $request->trainingScore;
            $rowData->save();

            log_action("{$rowData->user_email} scored {$request->trainingScore}% in training", 'learner', 'learner');

            if ($request->trainingScore == 100) {
                $rowData->completed = 1;
                $rowData->completion_date = now()->format('Y-m-d');
                $rowData->save();

                log_action("{$rowData->user_email} scored {$request->trainingScore}% in training", 'learner', 'learner');
            }
        }


        return response()->json(['message' => 'Score updated']);
    }


    public function bluecollarDownloadCertificate(Request $request)
    {
        // Get the necessary input from the request
        $trainingModule = $request->input('training_module');
        $trainingId = $request->input('training_id');
        $completionDate = $request->input('completion_date');
        $username = $request->input('username');

        // Check if the certificate ID already exists for this user and training module
        $certificateId = $this->getCertificateId($trainingModule, $username, $trainingId);

        // If the certificate ID doesn't exist, generate a new one
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeCertificateId($trainingModule, $username, $certificateId, $trainingId); // Store the new certificate ID in your database
        }

        // Generate the PDF from the view and include the certificate ID
        $pdf = Pdf::loadView('learning.certificate', compact('trainingModule', 'completionDate', 'username', 'certificateId'));

        // Define the filename with certificate ID
        $fileName = "{$trainingModule}_Certificate_{$certificateId}.pdf";

        log_action("Employee downloaded training certificate", 'learner', 'learner');
        // Return the PDF download response
        return $pdf->download($fileName);
    }

    /**
     * Get the certificate ID from the database (if it exists).
     */
    private function getCertificateId($trainingModule, $username, $trainingId)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = BlueCollarTrainingUser::where('training', $trainingId)
            ->where('user_email', $username)
            ->first();
        return $certificate ? $certificate->certificate_id : null;
    }

    private function generateCertificateId()
    {
        // Generate a unique random ID. You can adjust the format as needed.
        return strtoupper(uniqid('CERT-'));
    }
    private function storeCertificateId($trainingModule, $username, $certificateId, $trainingId)
    {
        // Find the existing record based on training module and username
        $assignedUser = BlueCollarTrainingUser::where('training', $trainingId)
            ->where('user_email', $username)
            ->first();

        // Check if the record was found
        if ($assignedUser) {

            // Update only the certificate_id (no need to touch campaign_id)
            $assignedUser->update([
                'certificate_id' => $certificateId,
            ]);
        }
    }
}
