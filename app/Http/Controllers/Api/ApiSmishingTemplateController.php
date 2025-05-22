<?php

namespace App\Http\Controllers\Api;

use Plivo\RestClient;
use Illuminate\Http\Request;
use App\Models\SmishingTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiSmishingTemplateController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->query('search')) {
                $templates = SmishingTemplate::where(function ($query) {
                    $query->where('company_id', Auth::user()->company_id)
                        ->orWhere('company_id', 'default');
                })
                    ->where('name', 'like', '%' . request()->query('search') . '%')
                    ->get();
            } else {
                $templates = SmishingTemplate::where('company_id', Auth::user()->company_id)
                    ->orWhere('company_id', 'default')
                    ->get();
            }
            return response()->json([
                'success' => true,
                'data' => $templates,
                'message' => 'Smishing Templates fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching smishing templates: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeTemplate(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'template_name' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value !== strip_tags($value)) {
                            $fail("The $attribute contains invalid characters.");
                        }
                    },
                ],
                'template_body' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        if (strpos($value, '{{user_name}}') === false || strpos($value, '{{redirect_url}}') === false) {
                            $fail("The template body must contain {{user_name}} and {{redirect_url}}.");
                        }
                    },
                ],
                'category' => 'required',
            ]);

            SmishingTemplate::create([
                'name' => $request->input('template_name'),
                'message' => $request->input('template_body'),
                'category' => $request->input('category'),
                'company_id' => Auth::user()->company_id,
            ]);
            log_action("Smishing Template created : {$request->input('template_name')}");
            return response()->json([
                'success' => true,
                'message' => 'Template created successfully.'
            ], 201);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating smishing template: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function testSms(Request $request)
    {
        try {
            $request->validate([
                'template_text' => 'required|string',
                'mobile_no' => 'required|regex:/^\+?[0-9]{10,15}$/',
            ]);

            $client = new RestClient(
                env('PLIVO_AUTH_ID'),
                env('PLIVO_AUTH_TOKEN')
            );

            $response = $client->messages->create(
                [
                    "src" => env('PLIVO_MOBILE_NUMBER'),
                    "dst" => $request->input('mobile_no'),
                    "text"  => $request->input('template_text')
                ]
            );
            log_action("Smishing SMS sent to : {$request->input('mobile_no')}");
            return response()->json([
                'status' => 'success',
                'message' => __('SMS sent successfully'),
                'response' => $response
            ]);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Plivo\Exceptions\PlivoRestException $e) {
            // Handle the Plivo exception
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send SMS: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateTemplate(Request $request)
    {
        try {
            $request->validate([
                'template_id' => 'required',
                'template_name' => function ($attribute, $value, $fail) {
                    if ($value !== strip_tags($value)) {
                        $fail("The $attribute contains invalid characters.");
                    }
                },
                'template_body' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        if (strpos($value, '{{user_name}}') === false || strpos($value, '{{redirect_url}}') === false) {
                            $fail("The template body must contain {{user_name}} and {{redirect_url}}.");
                        }
                    },
                ],
            ]);

            $updated = SmishingTemplate::where('id', $request->input('template_id'))
                ->update([
                    'name' => $request->input('template_name'),
                    'message' => $request->input('template_body'),
                    'category' => $request->input('category')

                ]);

            if (!$updated) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update template'
                ], 422);
            }
            log_action("Smishing Template updated to {$request->input('template_name')}");
            return response()->json([
                'success' => true,
                'message' => __('Template updated successfully.')
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

    public function deleteTemplate(Request $request)
    {
        try {
            $request->validate([
                'template_id' => 'required|exists:smishing_templates,id',
            ]);

            $smishingTemplate = SmishingTemplate::where('id', $request->input('template_id'))->first();
            $smishingTemplate->delete();

            log_action("Smishing Template deleted : {$smishingTemplate->name}");
            return response()->json([
                'status' => 'success',
                'message' => __('Template deleted successfully.')
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
