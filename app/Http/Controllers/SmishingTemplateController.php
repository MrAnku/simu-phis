<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SmishingTemplate;
use Dotenv\Exception\ValidationException;
use Illuminate\Support\Facades\Auth;
use Plivo\RestClient;

class SmishingTemplateController extends Controller
{
    public function index(Request $request)
    {
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
        return view('smishing-template', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        // Validate the request data
        $request->validate([

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

        SmishingTemplate::create([
            'name' => $request->input('template_name'),
            'message' => $request->input('template_body'),
            'category' => $request->input('category'),
            'company_id' => Auth::user()->company_id,
        ]);
        return redirect()->back()->with('success', 'Template created successfully.');



        // 
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
        $request->validate([

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

        SmishingTemplate::where('id', $request->input('template_id'))
            ->update([
                'name' => $request->input('template_name'),
                'message' => $request->input('template_body'),
                'category' => $request->input('category')

            ]);
        return redirect()->back()->with('success', __('Template updated successfully.'));
    }

    public function deleteTemplate(Request $request)
    {
        try {
            $request->validate([
                'template_id' => 'required|exists:smishing_templates,id',
            ]);

            SmishingTemplate::where('id', $request->input('template_id'))->delete();
            return response()->json([
                'status' => 'success',
                'message' => __('Template deleted successfully.')
            ]);
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
            //throw $th;
        }
    }
}
