<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TrainingCompleteMail;
use App\Models\CertificateTemplate;
use App\Models\TrainingAssignedUser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApiCustomCertificate extends Controller
{
    public function addCertificate(Request $request)
    {
        try {
            $request->validate([
                'template_html' => 'required|mimes:html',
                'layout_name' => 'required|string|max:255',
                'selected' => 'boolean',
            ]);
            $templateContent = file_get_contents($request->file('template_html')->getRealPath());

            // Validate required shortcodes
            $requiredShortcodes = [
                '{{logo_url}}',
                '{{certificate_id}}',
                '{{learner_name}}',
                '{{training_name}}',
                '{{completion_date}}'
            ];

            foreach ($requiredShortcodes as $shortcode) {
                if (strpos($templateContent, $shortcode) === false) {
                    return response()->json([
                        'success' => false,
                        'message' => "Missing required shortcode: $shortcode"
                    ], 422);
                }
            }

            $companyId = Auth::user()->company_id;

            $templatePath = '/certificate_templates/' . $companyId . '/' . uniqid() . '.html';

            // Save template to S3
            Storage::disk('s3')->put($templatePath, $templateContent);

            // Save template info in DB
            CertificateTemplate::create([
                'company_id' => Auth::user()->company_id,
                'filepath' => $templatePath,
                'layout_name' => $request->layout_name,
            ]);
            return response()->json(['success' => true, 'message' => 'Template saved successfully.'], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function setCustomCertificate(Request $request)
    {
        try {
            $request->validate([
                'template_id' => 'required|exists:certificate_templates,id',
                'selected' => 'required|boolean',
            ]);

            $companyId = Auth::user()->company_id;

            if ($request->selected) {
                // Deselect all templates for the company
                CertificateTemplate::where('company_id', $companyId)->update(['selected' => false]);
                // Select the specified template
                CertificateTemplate::where('id', $request->template_id)->update(['selected' => true]);
                $message = 'Template selected successfully.';
            } else {
                // Unselect the specified template
                CertificateTemplate::where('id', $request->template_id)->update(['selected' => false]);
                $message = 'Template unselected successfully.';
            }

            return response()->json(['success' => true, 'message' => $message], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function fetchCertificateTemplates()
    {
        try {
            $companyId = Auth::user()->company_id;
            $templates = CertificateTemplate::where('company_id', $companyId)->get();
            if ($templates->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No certificate templates found.'], 422);
            }
            return response()->json(['success' => true, 'message' => 'Certificate templates fetched successfully.', 'data' => $templates], 200);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Handle the exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
