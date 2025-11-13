<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\CustomTrainingEmail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Claims\Custom;

class ApiCustomTrainingEmailController extends Controller
{
    public function index()
    {
        $templates = CustomTrainingEmail::where('company_id', Auth::user()->company_id)->get();
        return response()->json([
            'success' => true,
            'message' => 'Custom Training Email Templates fetched successfully',
            'data' => $templates
        ], 200);
    }

    public function addTemplate(Request $request)
    {
        try {

            $request->validate([
                'template_name' => 'required|string|max:255',
                'email_subject' => 'required|string|max:255',
                'file' => [
                    'required',
                    'mimes:html,txt',
                    'max:1024', // Max 1MB file size
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            $content = file_get_contents($value->getRealPath());

                            // Check if content is not too long (max 1MB)
                            if (strlen($content) > 1048576) {
                                $fail(__('The HTML content is too long. Maximum 1MB allowed.'));
                            }

                            // Check for required shortcodes
                            $requiredShortcodes = ['{{user_name}}', '{{training_link}}', '{{assigned_trainings}}'];
                            foreach ($requiredShortcodes as $shortcode) {
                                if (strpos($content, $shortcode) === false) {
                                    $fail(__('The HTML must contain the shortcode:') . $shortcode);
                                }
                            }
                        }
                    }
                ],
            ]);

            $template = new CustomTrainingEmail();
            $template->company_id = Auth::user()->company_id;
            $template->template_name = $request->template_name;
            $template->email_subject = $request->email_subject;
            // Handle file upload and save the file path
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid('training_email_') . '.' . $extension;
            $s3Path = 'custom_training_template/' . Auth::user()->company_id;
            $filePath = $file->storeAs($s3Path, $fileName, 's3');
            $template->file_path = '/' . $filePath;
            $template->save();

            return response()->json([
                'success' => true,
                'message' => __('Custom Training Email Template added successfully. Please ensure the template is selected for default training emails.'),
                'data' => $template
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation Error: ') . $e->validator->errors()->first(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }
}
