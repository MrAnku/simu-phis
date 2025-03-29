<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Company;
use App\Models\UsersGroup;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TrainingModule;
use App\Models\WhatsappCampaign;
use App\Models\NewLearnerPassword;
use Illuminate\Support\Facades\DB;
use App\Mail\TrainingAssignedEmail;
use App\Models\WhatsappTempRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\CompanyWhatsappConfig;
use App\Models\CompanyWhatsappTemplate;
use App\Mail\AssignTrainingWithPassResetLink;
use App\Http\Requests\StoreWhatsAppTemplateRequest;
use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarGroup;
use App\Models\BlueCollarTrainingUser;
use App\Models\TrainingAssignedUser;

class WhatsappCampaignController extends Controller
{
    public function index()
    {
        $company_id = auth()->user()->company_id;
        $config = CompanyWhatsappConfig::where('company_id', $company_id)->first();
        if (!$config) {
            return view('whatsapp-unavailable');
        }
        $all_users = UsersGroup::where('company_id', $company_id)->get();
        $hasTemplates = CompanyWhatsappTemplate::where('company_id', $company_id)->first();
        if (!$hasTemplates) {
            $templates = [];
        } else {
            $templates = json_decode($hasTemplates->template, true)['data'];
        }

        $campaigns = WhatsappCampaign::with('trainingData')->where('company_id', $company_id)->orderBy('id', 'desc')
            ->paginate(10);
        $trainings = TrainingModule::where('company_id', $company_id)
            ->orWhere('company_id', 'default')->get();
        return view('whatsapp-campaign', compact('all_users', 'config', 'templates', 'campaigns', 'trainings'));
    }

    public function saveConfig(Request $request)
    {
        $validated = $request->validate([
            'from_phone_id' => 'required|numeric',
            'access_token' => 'required',
            'business_id' => 'required|numeric',
        ]);

        $company_id = auth()->user()->company_id;
        $validated['company_id'] = $company_id;

        CompanyWhatsappConfig::create($validated);

        return redirect()->back()->with('success', 'Configuration saved successfully!');
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'from_phone_id' => 'required|numeric',
            'access_token' => 'required',
            'business_id' => 'required|numeric',
        ]);

        $company_id = auth()->user()->company_id;

        CompanyWhatsappTemplate::where('company_id', $company_id)->delete();

        CompanyWhatsappConfig::where('company_id', $company_id)->update($validated);

        return redirect()->back()->with('success', 'Configuration updated successfully!');
    }

    public function syncTemplates()
    {
        $company_id = auth()->user()->company_id;
        $config = CompanyWhatsappConfig::where('company_id', $company_id)->first();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config->access_token
            ])->get('https://graph.facebook.com/v22.0/' . $config->business_id . '/message_templates');

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['error'])) {
                    return response()->json(['error' => $responseData['error']]);
                }
                CompanyWhatsappTemplate::where('company_id', $company_id)->delete();
                CompanyWhatsappTemplate::create([
                    'template' => json_encode($responseData),
                    'company_id' => $company_id
                ]);
                return response()->json(['success' => 'Templates synced successfully']);
            } else {
                $responseData = $response->json();
                if (isset($responseData['error'])) {
                    return response()->json(['error' => $responseData['error']['message']]);
                }
            }
        } catch (\Throwable $th) {
            return $response->json(['error' => 'Something went wrong']);
        }
    }


    public function submitCampaign(Request $request)
    {
        //xss check start

        $input = $request->only('camp_name');
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

        $company_id = auth()->user()->company_id;

        $new_campaign = new WhatsappCampaign();

        $new_campaign->camp_id = generateRandom(6);
        $camp_id = $new_campaign->camp_id;
        $new_campaign->camp_name = $request->camp_name;
        $new_campaign->template_name = $request->template_name;
        $new_campaign->user_group = $request->user_group;
        if ($request->empType == "Normal") {

            $new_campaign->user_group_name = $this->userGroupName($request->user_group);
        } else {
            $new_campaign->user_group_name = $this->BlueCollarGroupName($request->user_group);
        }
        $new_campaign->camp_type = $request->campType;
        $new_campaign->employee_type = $request->empType;

        if ($request->campType == "Phishing and Training") {
            $new_campaign->training = $request->training;
            $new_campaign->training_type = $request->trainingType;
        }
        $new_campaign->company_id = $company_id;
        $new_campaign->created_at = now();
        $new_campaign->save();

        $this->createCampaignIndividual($camp_id, $request);

        log_action('WhatsApp campaign created');

        return response()->json(['status' => 1, 'msg' => 'Campaign created successfully!']);
    }

    public function createCampaignIndividual($camp_id, $campaignData)
    {
        $users = [];

        if ($campaignData->empType == "Normal") {
            $users = Users::where('group_id', $campaignData->user_group)->get();
        } else {
            $users = BlueCollarEmployee::where('group_id', $campaignData->user_group)->get();
        }

        $company_id = Auth::user()->company_id;

        $training = ($campaignData->campType == "Phishing and Training") ? $campaignData->training : null;

        foreach ($users as $user) {

            if ($user->whatsapp == null) {
                continue;
            }
            DB::table('whatsapp_camp_users')->insert([
                'camp_id' => $camp_id,
                'camp_name' => $campaignData->camp_name,
                'user_group' => $campaignData->user_group,
                'user_name' => $user->user_name,
                'user_id' => $user->id,
                'user_email' => $user->user_email ?? null,
                'employee_type' => $campaignData->empType,
                'user_whatsapp' => $user->whatsapp,
                'template_name' => $campaignData->template_name,
                'template_language' => $campaignData->template_language,
                'training' => $training,
                'training_type' => $campaignData->trainingType ?? null,
                'components' => json_encode($campaignData->components ?? []),
                'status' => 'pending',
                'created_at' => now(),
                'company_id' => $company_id,
            ]);
        }
    }


    public function deleteCampaign(Request $request)
    {

        $request->validate([
            'campid' => 'required'
        ]);

        $campaign = WhatsappCampaign::where('camp_id', $request->campid)->first();

        if ($campaign) {

            $campaign->delete();
            DB::table('whatsapp_camp_users')->where('camp_id', $request->campid)->delete();

            log_action('WhatsApp campaign deleted');
            return response()->json(['status' => 1, 'msg' => 'Campaign deleted successfully']);
        } else {

            log_action('Something went wrong while deleting WhatsApp campaign');
            return response()->json(['status' => 0, 'msg' => 'Something went wrong!']);
        }
    }

    private function userGroupName($groupid)
    {
        $userGroup = UsersGroup::where('group_id', $groupid)->first();
        if ($userGroup) {
            return $userGroup->group_name;
        } else {
            return null;
        }
    }

    private function BlueCollarGroupName($groupid)
    {
        $userGroup = BlueCollarGroup::where('group_id', $groupid)->first();
        if ($userGroup) {
            return $userGroup->group_name;
        } else {
            return null;
        }
    }

    public function fetchCampaign(Request $request)
    {
        $company_id = auth()->user()->company_id;

        $campaign = DB::table('whatsapp_camp_users')->where('camp_id', $request->campid)->where('company_id', $company_id)->get();

        return response()->json($campaign);
    }

    public function showWebsite($campaign_id)
    {

        log_action('WhatsApp phishing website visited', 'employee', 'employee');

        return view('whatsapp-website', compact('campaign_id'));
    }
    public function startTrainingWebsitee($assigntraining_id)
    {

        $decoded_id = base64_decode($assigntraining_id);

        if (!$decoded_id || !is_numeric($decoded_id)) {
            return back()->with('error', 'Invalid User ID');
        }

        // $User = DB::table('blue_collar_training_users')->where('id', $decoded_id)->first();
        $User = BlueCollarTrainingUser::with('trainingModule')->find($decoded_id);
        // return $User;
        $training_module_name = $User->trainingModule->name;
        // dd($User->trainingModule->name);

        if ($User) {
            return view('start-training', compact('training_module_name'));
        } else {
            return back()->with('error', 'Invalid User');
        }
    }




    public function updatePayload(Request $request)
    {

        $cid = base64_decode($request->cid);

        $user = DB::table('whatsapp_camp_users')->where('id', $cid)->where('link_clicked', 0)->first();

        if ($user) {
            DB::table('whatsapp_camp_users')->where('id', $cid)->update(['link_clicked' => 1]);

            log_action('WhatsApp phishing payload clicked', 'employee', 'employee');

            return response()->json(['status' => 1, 'msg' => 'Payload updated']);
        } else {

            log_action('WhatsApp phishing | Payload not updated because of invalid campaign id', 'employee', 'employee');

            return response()->json(['status' => 0, 'msg' => 'Invalid cid']);
        }
    }

    public function updateEmpComp(Request $request)
    {
        $cid = base64_decode($request->cid);

        $user = DB::table('whatsapp_camp_users')->where('id', $cid)->where('emp_compromised', 0)->first();

        if ($user) {
            DB::table('whatsapp_camp_users')->where('id', $cid)->update(['emp_compromised' => 1]);

            log_action('Employee compromised in WhatsApp campaign', 'employee', 'employee');

            return response()->json(['status' => 1, 'msg' => 'emp compromised updated']);
        } else {
            return response()->json(['status' => 0, 'msg' => 'Invalid cid']);
        }
    }

    public function newTemplate(StoreWhatsAppTemplateRequest $request)
    {
        //xss check start

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

        //xss check end

        $company_id = auth()->user()->company_id;
        $partner_id = auth()->user()->partner_id;

        // Validation has already been performed at this point
        $validated = $request->validated();

        // Store the template
        $template = new WhatsappTempRequest();
        $template->template_name = $validated['temp_name'];
        $template->template_body = $validated['temp_body'];
        $template->company_id = $company_id;
        $template->partner_id = $partner_id;
        $template->created_at = now();
        $template->save();

        log_action('New WhatsApp templete requested');

        return redirect()->back()->with('success', 'New template request added successfully.');
    }

    public function assignTraining(Request $request)
    {
        $cid = base64_decode($request->cid);


        $campaign_user = DB::table('whatsapp_camp_users')
            ->where('id', $cid)
            ->first();

        if (!$campaign_user) {
            return response()->json(['error' => 'Invalid campaign id']);
        }
        if ($campaign_user->training == null) {
            return response()->json(['error' => 'No training assigned']);
        }
        $training =  DB::table('training_modules')
            ->where('id', $campaign_user->training)
            ->first();

        if (!$training) {
            return response()->json(['error' => 'Training not found']);
        }

        if ($campaign_user->employee_type == "Bluecollar") {
            $already_have_this_training = DB::table('blue_collar_training_users')
                ->where('user_id', $campaign_user->user_id)
                ->where('training', $campaign_user->training)
                ->first();

            if (!$training) {
                return response()->json(['error' => 'Training not found']);
            }
            if ($already_have_this_training) {
                // return "Send Remainder";
                return $this->whatsappSendTrainingReminder($campaign_user, $training, $already_have_this_training);
            } else {
                // return "Assign Training";
                return $this->whatsappAssignFirstTraining($campaign_user, $training);
            }
        }

        //training exists or not

        // Check if training is already assigned to the user
        $already_have_this_training = DB::table('training_assigned_users')
            ->where('user_email', $campaign_user->user_email)
            ->where('training', $campaign_user->training)
            ->first();

        if ($already_have_this_training) {

            //this training is already assigned then send reminder about this training
            return $this->sendTrainingReminder($campaign_user, $training);
        } else {
            // Check if user login already exists
            $user_have_login = DB::table('user_login')
                ->where('login_username', $campaign_user->user_email)
                ->first();

            if ($user_have_login) {

                //this user have login assign another training

                return $this->assignAnotherTraining($campaign_user, $training, $user_have_login);
            } else {
                // This user don't have any login details assign training first time

                return $this->assignFirstTraining($campaign_user, $training);
            }
        }
    }


    private function assignFirstTraining($campaign_user, $training)
    {

        $training_assigned = DB::table('training_assigned_users')
            ->insert([
                'campaign_id' => $campaign_user->camp_id,
                'user_id' => $campaign_user->user_id,
                'user_name' => $campaign_user->user_name,
                'user_email' => $campaign_user->user_email,
                'training' => $campaign_user->training,
                'training_lang' => 'en',
                'training_type' => $campaign_user->training_type,
                'assigned_date' => now()->toDateString(),
                'training_due_date' => now()->addDays(14)->toDateString(),
                'company_id' => $campaign_user->company_id
            ]);
        if (!$training_assigned) {
            return response()->json(['error' => 'Failed to assign training']);
        }

        $learnSiteAndLogo = $this->checkWhitelabeled($campaign_user->company_id);
        $token = encrypt($campaign_user->user_email);

        $passwordGenLink = env('APP_URL') . '/learner/create-password/' . $token;
        $token = encrypt($campaign_user->user_email);
        // $token = Hash::make($campaign->user_email);
        $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
        DB::table('learnerloginsession')
            ->insert([
                'token' => $token,
                'email' => $campaign_user->user_email,
                'expiry' => now()->addHours(24), // Ensure it expires in 24 hours
                'created_at' => now(), // Ensure ordering works properly
            ]);
        $mailData = [
            'user_name' => $campaign_user->user_name,
            'training_name' => $training->name,
            'password_create_link' => $learning_dashboard_link,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learning_dashboard_link,
            'logo' => $learnSiteAndLogo['logo']
        ];

        log_action("WhatsApp simulation | Training {$training->name} assigned to {$campaign_user->user_email}.", 'employee', 'employee');

        $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campaign_user->user_email)->get();

        $trainingNames = $allAssignedTrainings->map(function ($training) {
            if ($training->training_type == 'games') {
                return $training->trainingGame->name;
            }
            return $training->trainingData->name;
        });

        Mail::to($campaign_user->user_email)->send(new AssignTrainingWithPassResetLink($mailData, $trainingNames));

        NewLearnerPassword::create([
            'email' => $campaign_user->user_email,
            'token' => $token
        ]);

        DB::table('whatsapp_camp_users')
            ->where('id', $campaign_user->id)
            ->update(['training_assigned' => 1]);

        return response()->json(['success' => 'First assigned successfully']);
    }
    private function whatsappAssignFirstTraining($campaign_user, $training)
    {
        $training_assigned = DB::table('blue_collar_training_users')
            ->insertGetId([
                'campaign_id' => $campaign_user->camp_id,
                'user_id' => $campaign_user->user_id,
                'user_name' => $campaign_user->user_name,
                'user_whatsapp' => $campaign_user->user_whatsapp,
                'training' => $campaign_user->training,
                'training_lang' => 'en',
                'training_type' => $campaign_user->training_type,
                'assigned_date' => now()->toDateString(),
                'training_due_date' => now()->addDays(14)->toDateString(),
                'company_id' => $campaign_user->company_id
            ]);



        if (!$training_assigned) {
            return response()->json(['error' => 'Failed to assign training']);
        }

        $learnSiteAndLogo = $this->checkWhitelabeled($campaign_user->company_id);
        $token = encrypt($campaign_user->user_email);
        $passwordGenLink = env('APP_URL') . '/learner/create-password/' . $token;

        DB::table('whatsapp_camp_users')
            ->where('id', $campaign_user->id)
            ->update(['training_assigned' => 1]);

        // WhatsApp Notification
        $access_token = "EAAeHyugovWQBO4UNW4Hq7ERYRw9AGF4tgQRmG9xN0K56OkMBCjHkv2jKqrdHl6ZCtFBh7NhFflvhLIigw8G0mTKujiy3FRQTMCo8veibn1OWDAHaliPm9zHTS1RQ599PMhZA8bivHPZCNlXe3FnLkNtAyq19GkCIZBfVyG2wYFBJHsN2i4ZByk9ZC05tGdY7lP9wZDZD";
        $phone_number_id = "338729722654932";
        $whatsapp_url = "https://graph.facebook.com/v22.0/{$phone_number_id}/messages";

        $whatsapp_data = [
            "messaging_product" => "whatsapp",
            "to" => "919122668359",
            "type" => "template",
            "template" => [
                "name" => "training_message",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $campaign_user->user_name],
                            ["type" => "text", "text" => $training->name],
                            ["type" => "text", "text" => "https://" . Str::random(3) . "." . env('PHISHING_WEBSITE_DOMAIN') . "/start-training/" . base64_encode($training_assigned),]
                        ]
                    ]
                ]
            ]
        ];

        $whatsapp_response = Http::withHeaders([
            "Authorization" => "Bearer {$access_token}",
            "Content-Type" => "application/json"
        ])->withOptions([
            'verify' => false
        ])->post($whatsapp_url, $whatsapp_data);

        log_action("WhatsApp API Response: " . $whatsapp_response->body(), 'employee', 'employee');

        if ($whatsapp_response->successful()) {
            log_action("WhatsApp Training Assigned | Training {$training->name} assigned to 919122668359.", 'employee', 'employee');
        } else {
            log_action("WhatsApp message failed | Status: " . $whatsapp_response->status(), 'employee', 'employee');
        }

        return response()->json(['success' => 'Training assigned and WhatsApp notification sent']);
    }

    private function assignAnotherTraining($campaign_user, $training, $user_have_login)
    {

        // Insert into training_assigned_users table

        $res2 = DB::table('training_assigned_users')
            ->insert([
                'campaign_id' => $campaign_user->camp_id,
                'user_id' => $campaign_user->user_id,
                'user_name' => $campaign_user->user_name,
                'user_email' => $campaign_user->user_email,
                'training' => $campaign_user->training,
                'training_lang' => 'en',
                'training_type' => $campaign_user->training_type,
                'assigned_date' => now()->toDateString(),
                'training_due_date' => now()->addDays(14)->toDateString(),
                'company_id' => $campaign_user->company_id
            ]);

        if ($res2) {
            // echo "user created successfully";

            $learnSiteAndLogo = $this->checkWhitelabeled($campaign_user->company_id);
            $token = encrypt($campaign_user->user_email);
            // $token = Hash::make($campaign->user_email);
            $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
            DB::table('learnerloginsession')
                ->insert([
                    'token' => $token,
                    'email' => $campaign_user->user_email,
                    'expiry' => now()->addHours(24), // Ensure it expires in 24 hours
                    'created_at' => now(), // Ensure ordering works properly
                ]);
            $mailData = [
                'user_name' => $campaign_user->user_name,
                'training_name' => $training->name,
                'login_email' => $user_have_login->login_username,
                'login_pass' => $user_have_login->login_password,
                'company_name' => $learnSiteAndLogo['company_name'],
                'company_email' => $learnSiteAndLogo['company_email'],
                'learning_site' =>  $learning_dashboard_link,
                'logo' => $learnSiteAndLogo['logo']
            ];

            log_action("WhatsApp simulation | Training {$campaign_user->training} assigned to {$campaign_user->user_email}.", 'employee', 'employee');

            $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campaign_user->user_email)->get();

            $trainingNames = $allAssignedTrainings->map(function ($training) {
                if ($training->training_type == 'games') {
                    return $training->trainingGame->name;
                }
                return $training->trainingData->name;
            });

            Mail::to($campaign_user->user_email)->send(new TrainingAssignedEmail($mailData, $trainingNames));

            // Update campaign_live table
            DB::table('whatsapp_camp_users')
                ->where('id', $campaign_user->id)
                ->update(['training_assigned' => 1]);

            return response()->json(['success' => 'Another training has assigned successfully']);
        } else {

            log_action("WhatsApp simulation | Failed to assign training", 'employee', 'employee');

            return response()->json(['error' => 'Failed to assign another training']);
        }
    }

    private function sendTrainingReminder($campaign_user, $training)
    {
        // Fetch user credentials
        $userCredentials = DB::table('user_login')
            ->where('login_username', $campaign_user->user_email)
            ->first();

        $learnSiteAndLogo = $this->checkWhitelabeled($campaign_user->company_id);

        $token = encrypt($campaign_user->user_email);
        // $token = Hash::make($campaign->user_email);
        $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
        DB::table('learnerloginsession')
            ->insert([
                'token' => $token,
                'email' => $campaign_user->user_email,
                'expiry' => now()->addHours(24), // Ensure it expires in 24 hours
                'created_at' => now(), // Ensure ordering works properly
            ]);
        $mailData = [
            'user_name' => $campaign_user->user_name,
            'training_name' => $training->name,
            // 'login_email' => $userCredentials->login_username,
            // 'login_pass' => $userCredentials->login_password,
            'company_name' => $learnSiteAndLogo['company_name'],
            'company_email' => $learnSiteAndLogo['company_email'],
            'learning_site' => $learning_dashboard_link,
            'logo' => $learnSiteAndLogo['logo']
        ];

        log_action("WhatsApp simulation | Training {$training->name} already assigned to {$campaign_user->user_email}, Reminder Sent.", 'employee', 'employee');

        $allAssignedTrainings = TrainingAssignedUser::with('trainingData', 'trainingGame')->where('user_email', $campaign_user->user_email)->get();

        $trainingNames = $allAssignedTrainings->map(function ($training) {
            if ($training->training_type == 'games') {
                return $training->trainingGame->name;
            }
            return $training->trainingData->name;
        });

        Mail::to($campaign_user->user_email)->send(new TrainingAssignedEmail($mailData, $trainingNames));

        return response()->json(['success' => 'Training reminder has sent']);
    }


    private function whatsappSendTrainingReminder($campaign_user, $training, $already_have_this_training)
    {
        // return $already_have_this_training->id;
        // Fetch user credentials
        $userCredentials = DB::table('user_login')
            ->where('login_username', $campaign_user->user_email)
            ->first();

        $learnSiteAndLogo = $this->checkWhitelabeled($campaign_user->company_id);
        // return $learnSiteAndLogo;
        log_action("WhatsApp simulation | Training {$training->name} already assigned to {$campaign_user->user_email}, Reminder Sent.", 'employee', 'employee');

        // WhatsApp API Configuration
        $access_token = "EAAeHyugovWQBO4UNW4Hq7ERYRw9AGF4tgQRmG9xN0K56OkMBCjHkv2jKqrdHl6ZCtFBh7NhFflvhLIigw8G0mTKujiy3FRQTMCo8veibn1OWDAHaliPm9zHTS1RQ599PMhZA8bivHPZCNlXe3FnLkNtAyq19GkCIZBfVyG2wYFBJHsN2i4ZByk9ZC05tGdY7lP9wZDZD";
        $phone_number_id = "338729722654932";
        $whatsapp_url = "https://graph.facebook.com/v22.0/{$phone_number_id}/messages";


        // WhatsApp Message Data
        // return $already_have_this_training->id;
        $whatsapp_data = [
            "messaging_product" => "whatsapp",
            "to" => $campaign_user->user_whatsapp, // Replace with actual user phone number
            "type" => "template",
            "template" => [
                "name" => "training_message",
                "language" => ["code" => "en"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $campaign_user->user_name],
                            ["type" => "text", "text" => $training->name],
                            ["type" => "text", "text" => "https://" . Str::random(3) . "." . env('PHISHING_WEBSITE_DOMAIN') . "/start-training/" . base64_encode($already_have_this_training->id)],
                        ]
                    ]
                ]
            ]
        ];

        // Send WhatsApp message

        $whatsapp_response = Http::withHeaders([
            "Authorization" => "Bearer {$access_token}",
            "Content-Type" => "application/json"
        ])->withOptions([
            'verify' => false
        ])->post($whatsapp_url, $whatsapp_data);

        // return $whatsapp_data;
        // Log response
        log_action("WhatsApp API Response: " . $whatsapp_response->body(), 'employee', 'employee');

        if ($whatsapp_response->successful()) {
            log_action("WhatsApp Reminder Sent | Training {$training->name} assigned to 919122668359.", 'employee', 'employee');
            return response()->json(['success' => 'Training reminder sent via WhatsApp']);
        } else {
            return response()->json([
                'error' => 'Failed to send WhatsApp message',
                'status' => $whatsapp_response->status(),
                'response' => $whatsapp_response->body()
            ], 500);
        }
    }

    // Function to check if the campaign is whitelabeled
    private function checkWhitelabeled($company_id)
    {
        $company = Company::with('partner')->where('company_id', $company_id)->first();

        $partner_id = $company->partner->partner_id;
        $company_email = $company->email;

        $isWhitelabled = DB::table('white_labelled_partner')
            ->where('partner_id', $partner_id)
            ->where('approved_by_admin', 1)
            ->first();

        if ($isWhitelabled) {
            return [
                'company_email' => $company_email,
                'learn_domain' => $isWhitelabled->learn_domain,
                'company_name' => $isWhitelabled->company_name,
                'logo' => env('APP_URL') . '/storage/uploads/whitelabeled/' . $isWhitelabled->dark_logo
            ];
        }

        return [
            'company_email' => env('MAIL_FROM_ADDRESS'),
            'learn_domain' => 'learn.simuphish.com',
            'company_name' => 'simUphish',
            'logo' => env('APP_URL') . '/assets/images/simu-logo-dark.png'
        ];
    }
}
