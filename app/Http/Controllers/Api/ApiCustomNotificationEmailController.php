<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomNotificationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApiCustomNotificationEmailController extends Controller
{
    public function index()
    {
        $templates = CustomNotificationEmail::where('company_id', Auth::user()->company_id)->get();
        return response()->json([
            'success' => true,
            'message' => __('Custom notification email templates fetched successfully'),
            'data' => $templates
        ], 200);
    }

    public function getTemplateById($id)
    {
        try {
            $id = base64_decode($id);
            $template = CustomNotificationEmail::where('company_id', Auth::user()->company_id)->where('id', $id)->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => __('Notification email template not found.'),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('Custom notification email template fetched successfully.'),
                'data' => $template
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
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

            $template = new CustomNotificationEmail();
            $template->company_id = Auth::user()->company_id;
            $template->template_name = $request->template_name;
            $template->email_subject = $request->email_subject;
            // Handle file upload and save the file path
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid('notification_email_') . '.' . $extension;
            $s3Path = 'custom_notification_template/' . Auth::user()->company_id;
            $filePath = $file->storeAs($s3Path, $fileName, 's3');
            $template->file_path = '/' . $filePath;
            $template->save();

            return response()->json([
                'success' => true,
                'message' => __('Custom notification email template added successfully. Please ensure the template is selected for default notification emails.'),
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

    public function selectDeselectTemplate($id)
    {
        try {
            $id = base64_decode($id);
            $template = CustomNotificationEmail::where('company_id', Auth::user()->company_id)->where('id', $id)->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => __('Notification email template not found.'),
                ], 404);
            }

            // Deselect all templates
            CustomNotificationEmail::where('company_id', Auth::user()->company_id)->update(['status' => false]);

            // Select the specified template
            if (!$template->status) {

                $template->status = true;
                $msg = __("Custom notification email template selected successfully. All notification emails will be sent using this template.");
            } else {
                $template->status = false;
                $msg = __("Custom notification email template deselected. Default notification email template will be used.");
            }
            $template->save();

            return response()->json([
                'success' => true,
                'message' => $msg
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function updateTemplate(Request $request, $id)
    {
        try {
            $id = base64_decode($id);
            $template = CustomNotificationEmail::where('company_id', Auth::user()->company_id)->where('id', $id)->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => __('Notification email template not found.'),
                ], 404);
            }

            $request->validate([
                'template_name' => 'sometimes|required|string|max:255',
                'email_subject' => 'sometimes|required|string|max:255',
                'file' => [
                    'sometimes',
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

            if ($request->has('template_name')) {
                $template->template_name = $request->template_name;
            }
            if ($request->has('email_subject')) {
                $template->email_subject = $request->email_subject;
            }
            if ($request->hasFile('file')) {
                // Update the content of the existing file in S3
                $file = $request->file('file');
                $content = file_get_contents($file->getRealPath());

                // Update existing file content
                $s3 = Storage::disk('s3');
                $s3->put(ltrim($template->file_path, '/'), $content);
            }

            $template->save();

            return response()->json([
                'success' => true,
                'message' => __('Custom notification email template updated successfully.')
            ], 200);
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

    public function deleteTemplate($id)
    {
        try {
            $id = base64_decode($id);
            $template = CustomNotificationEmail::where('company_id', Auth::user()->company_id)->where('id', $id)->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => __('Notification email template not found.'),
                ], 404);
            }

            //delete from s3
            if ($template->file_path) {
                $s3 = Storage::disk('s3');
                if ($s3->exists(ltrim($template->file_path, '/'))) {
                    $s3->delete(ltrim($template->file_path, '/'));
                }
            }

            $template->delete();

            return response()->json([
                'success' => true,
                'message' => __('Custom notification email template deleted successfully.'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }
}
