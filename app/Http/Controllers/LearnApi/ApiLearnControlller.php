<?php

namespace App\Http\Controllers\LearnApi;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AssignedPolicy;
use App\Models\TrainingModule;
use Illuminate\Support\Carbon;
use App\Models\WhiteLabelledSmtp;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Mail;
use App\Mail\LearnerSessionRegenerateMail;
use Illuminate\Validation\ValidationException;

class ApiLearnControlller extends Controller
{
    public function loginWithToken(Request $request)
    {
        try {
            $request->validate([
                'training_dashboard_link' => 'required|url',
            ]);
            $url = $request->training_dashboard_link;
            $segments = Str::of(parse_url($url, PHP_URL_PATH))->explode('/');
            $token = $segments[2] ?? null;

            $session = DB::table('learnerloginsession')->where('token', $token)->orderBy('created_at', 'desc') // Ensure the latest session is checked
                ->first();
            if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token expired'
                ], 422);
            }

            return response()->json([
                'status' => true,
                'message' => 'You can Login now'
            ], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createNewToken(Request $request)
    {
        try {
            $email = $request->query('email');
            $request->validate(['email' => 'required|email']);

            $hasTraining = TrainingAssignedUser::where('user_email', $email)->exists();

            $hasPolicy = AssignedPolicy::where('user_email', $email)->exists();

            if (!$hasTraining && !$hasPolicy) {
                return response()->json(['error' => 'No training or policy has been assigned to this email.'], 500);
            }

            // delete old generated tokens from db
            DB::table('learnerloginsession')->where('email', $email)->delete();

            // Encrypt email to generate token
            $token = encrypt($email);

            if ($hasPolicy && !$hasTraining) {
                $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/policies/' . $token;
            } else {
                $learning_dashboard_link = env('SIMUPHISH_LEARNING_URL') . '/training-dashboard/' . $token;
            }

            // Insert new record into the database
            $inserted = DB::table('learnerloginsession')->insert([
                'email' => $email,
                'token' => $token,
                'expiry' => now()->addHours(24),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Check if the record was inserted successfully
            if (!$inserted) {
                return response()->json(['status' => 'error', 'message' => 'Failed to create token'], 500);
            }

            // Prepare email data
            $mailData = [
                'learning_site' => $learning_dashboard_link,
            ];

            $trainingModules = TrainingModule::where('company_id', 'default')->take(5)->get();
            // Send email
            Mail::to($email)->send(new LearnerSessionRegenerateMail($mailData, $trainingModules));

            // Return success response
            return response()->json(['status' => true, 'message' => 'Mail sent successfully'], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
