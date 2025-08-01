<?php

namespace App\Http\Controllers;

use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ScormTraining;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    public function translate(Request $request)
    {
        $apiKey = env('OPENAI_API_KEY');
        $apiEndpoint = "https://api.openai.com/v1/completions";
        $file = public_path($request->query('fileurl'));
        $fileContent = file_get_contents($file);

        // return response($fileContent, 200)->header('Content-Type', 'text/html');

        $prompt = "Translate the following email content to {$request->query('lang')}:\n\n{$fileContent}";

        $requestBody = [
            'model' => 'gpt-3.5-turbo-instruct',
            'prompt' => $prompt,
            'max_tokens' => 1500,
            'temperature' => 0.7,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post($apiEndpoint, $requestBody);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch translation' . $response->body()], 500);
        }
        $responseData = $response->json();
        $translatedMailBody = $responseData['choices'][0]['text'] ?? null;

        return response($translatedMailBody, 200)->header('Content-Type', 'text/html');
    }

    public function testScorm($id)
    {
        // Validate input
        if (!is_numeric($id) || $id <= 0) {
            return response()->json(['error' => 'Invalid ID'], 400);
        }

        // Find SCORM training
        try {
            $scormTraining = ScormTraining::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'SCORM training not found'], 404);
        }

        $s3FilePath = $scormTraining->file_path;

        // Validate S3 file path
        if (empty($s3FilePath) || !Str::endsWith($s3FilePath, '.zip')) {
            return response()->json(['error' => 'Invalid or missing ZIP file path'], 400);
        }

        // Generate a safe directory name for extraction
        $extractDirName = md5($s3FilePath); // Use hash to avoid unsafe characters
        $publicExtractPath = storage_path('app/public/scorm_packages/' . $extractDirName);
        $extractUrl = url('storage/scorm_packages/' . $extractDirName);

        // Check if already extracted
        if (file_exists($publicExtractPath . '/imsmanifest.xml')) {
            return response()->json([
                'scormTraining' => $scormTraining,
                'firstFileUrl' => $extractUrl . '/' . $scormTraining->entry_point,
            ]);
        }

        // Create extraction directory
        if (!file_exists($publicExtractPath) && !mkdir($publicExtractPath, 0755, true)) {
            return response()->json(['error' => 'Failed to create extraction directory'], 500);
        }

        // Create temporary directory
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir) && !mkdir($tempDir, 0755, true)) {
            return response()->json(['error' => 'Failed to create temporary directory'], 500);
        }

        // Download ZIP file to a temporary path
        $localZipPath = tempnam($tempDir, 'scorm_');
        if ($localZipPath === false) {
            return response()->json(['error' => 'Failed to create temporary file'], 500);
        }

        try {
            // Download file from S3
            $fileContents = Storage::disk('s3')->get($s3FilePath);
            if (file_put_contents($localZipPath, $fileContents) === false) {
                unlink($localZipPath);
                return response()->json(['error' => 'Failed to write ZIP file to disk'], 500);
            }

            // Extract ZIP file
            $zip = new ZipArchive;
            if ($zip->open($localZipPath) !== true) {
                unlink($localZipPath);
                return response()->json(['error' => 'Failed to open ZIP file: ' . $zip->getStatusString()], 500);
            }

            $zip->extractTo($publicExtractPath);
            $zip->close();
        } catch (\Exception $e) {
            if (file_exists($localZipPath)) {
                unlink($localZipPath);
            }
            return response()->json(['error' => 'Failed to process ZIP file: ' . $e->getMessage()], 500);
        }

        // Clean up temporary file
        if (file_exists($localZipPath)) {
            unlink($localZipPath);
        }

        // Verify SCORM package
        if (!file_exists($publicExtractPath . '/imsmanifest.xml')) {
            // Optionally clean up extracted directory
            Storage::deleteDirectory('public/scorm_packages/' . $extractDirName);
            return response()->json(['error' => 'Invalid SCORM package: Missing imsmanifest.xml'], 400);
        }

        // Return response
        return response()->json([
            'scormTraining' => $scormTraining,
            'firstFileUrl' => $extractUrl . '/' . $scormTraining->entry_point,
        ]);
    }
}
