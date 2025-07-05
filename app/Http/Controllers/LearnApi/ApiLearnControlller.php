<?php

namespace App\Http\Controllers\LearnApi;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AssignedPolicy;
use App\Models\TrainingModule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use App\Mail\LearnerSessionRegenerateMail;
use App\Models\BlueCollarEmployee;
use App\Models\BlueCollarTrainingUser;
use App\Models\Users;
use Illuminate\Validation\ValidationException;

class ApiLearnControlller extends Controller
{
    public function loginWithToken(Request $request)
    {
        try {
            $token = $request->query('token');

            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token is required!'
                ], 422);
            }

            $session = DB::table('learnerloginsession')->where('token', $token)->orderBy('created_at', 'desc') // Ensure the latest session is checked
                ->first();
            if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your training session has expired!'
                ], 422);
            }

            // Decrypt the email
            $userEmail = decrypt($session->token);

            $averageScore = DB::table('training_assigned_users')
                ->where('user_email', $userEmail)
                ->avg('personal_best');

            $assignedTraining = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $userEmail)
                ->where('completed', 0)
                ->get();

            $completedTraining = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $userEmail)
                ->where('completed', 1)
                ->get();

            $totalCertificates = TrainingAssignedUser::where('user_email', $userEmail)
                ->where('completed', 1)
                ->where('personal_best', 100)
                ->count();

            Session::put('token', $token);

            return response()->json([
                'status' => true,
                'data' => [
                    'email' => $userEmail,
                    'average_score' => $averageScore,
                    'assigned_training' => $assignedTraining,
                    'completed_training' => $completedTraining,
                    'total_certificates' => $totalCertificates,
                ],
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
                return response()->json(['error' => 'No training or policy has been assigned to this email.'], 422);
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
                return response()->json(['status' => 'error', 'message' => 'Failed to create token'], 422);
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

    public function getNormalEmpTranings(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $user = Users::where('user_email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'No user found with this email.'
                ], 404);
            }

            $assignedTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)
                ->where('completed', 0)->get();

            $completedTrainings = TrainingAssignedUser::with('trainingData')
                ->where('user_email', $request->email)
                ->where('completed', 1)->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'email' => $request->email,
                    'assigned_trainings' => $assignedTrainings,
                    'completed_trainings' => $completedTrainings,
                ],
                'message' => 'Courses fetched successfully for ' . $request->email
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

    public function getBlueCollarEmpTranings(Request $request)
    {
        try {
            $request->validate([
                'user_whatsapp' => 'required|integer',
            ]);

            $blueCollarUser = BlueCollarEmployee::where('whatsapp', $request->user_whatsapp)->first();
            if (!$blueCollarUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'No blue collar employee found with this WhatsApp number.'
                ], 404);
            }
            $assignedTrainings = BlueCollarTrainingUser::with('trainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('completed', 0)->get();

            $completedTrainings = BlueCollarTrainingUser::with('trainingData')
                ->where('user_whatsapp', $request->user_whatsapp)
                ->where('completed', 1)->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'user_whatsapp' => $request->user_whatsapp,
                    'assigned_trainings' => $assignedTrainings,
                    'completed_trainings' => $completedTrainings,
                ],
                'message' => 'Courses fetched successfully for blue collar employee'
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
}
