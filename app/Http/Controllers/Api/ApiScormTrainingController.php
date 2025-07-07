<?php

namespace App\Http\Controllers\Api;

use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ScormTraining;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApiScormTrainingController extends Controller
{
    private function checkS3Configuration()
    {
        try {
            $s3Config = config('filesystems.disks.s3');

            Log::info("S3 Configuration Debug:", [
                'key' => $s3Config['key'] ? 'SET (****)' : 'NOT SET',
                'secret' => $s3Config['secret'] ? 'SET (****)' : 'NOT SET',
                'bucket' => $s3Config['bucket'] ?? 'NOT SET',
                'region' => $s3Config['region'] ?? 'NOT SET',
                'endpoint' => $s3Config['endpoint'] ?? 'NOT SET',
                'use_path_style_endpoint' => $s3Config['use_path_style_endpoint'] ?? false,
            ]);

            // Check if required S3 configuration keys exist
            $requiredKeys = ['key', 'secret', 'bucket', 'region'];
            foreach ($requiredKeys as $key) {
                if (!isset($s3Config[$key]) || empty($s3Config[$key])) {
                    Log::error("S3 configuration missing: $key");
                    return false;
                }
            }

            Log::info("S3 configuration validated successfully");
            return true;
        } catch (\Exception $e) {
            Log::error("S3 configuration check failed: " . $e->getMessage());
            return false;
        }
    }

    public function addScormTraining(Request $request)
    {
        // return $request;
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'scorm_file' => 'required|file|mimes:zip|max:921600',
                'passing_score' => 'required|numeric',
                'entry_point' => 'nullable|string|max:255',
            ]);

            $companyId = Auth::user()->company_id;
            $slug = Str::slug($request->name) . '-' . time();

            $file = $request->file('scorm_file');




            // Generate a random name for the file
            $randomName = generateRandom(32);
            $extension = $file->getClientOriginalExtension();
            $newFilename = $randomName . '.' . $extension;

            $filePath = $request->file('scorm_file')->storeAs('/uploads/scorm', $newFilename, 's3');





            // Log::info("Starting SCORM upload process", [
            //     'company_id' => $companyId,
            //     'slug' => $slug,
            //     'file_name' => $file->getClientOriginalName(),
            //     'file_size' => $file->getSize()
            // ]);

            // // Step 1: Create temp directories
            // $tmpDir = storage_path("app/tmp");
            // if (!File::exists($tmpDir)) {
            //     File::makeDirectory($tmpDir, 0755, true);
            // }

            // // Step 2: Move uploaded file to temp directory
            // $folderName = Str::random(32);
            // $zipTempPath = storage_path("app/tmp/{$folderName}.zip");
            // $file->move(storage_path("app/tmp"), "{$folderName}.zip");
            // Log::info("ZIP file moved to: $zipTempPath");

            // // Step 3: Extract ZIP file
            // $extractPath = storage_path("app/tmp/{$folderName}");
            // $zip = new ZipArchive;

            // if ($zip->open($zipTempPath) !== TRUE) {
            //     Log::error("Failed to open ZIP file: $zipTempPath");
            //     return response()->json(['error' => 'Unable to extract SCORM package'], 500);
            // }

            // if (!File::exists($extractPath)) {
            //     File::makeDirectory($extractPath, 0755, true);
            // }

            // $zip->extractTo($extractPath);
            // $zip->close();
            // Log::info("ZIP file extracted to: $extractPath");

            // // Step 4: Find entry point from imsmanifest.xml
            // $entryPoint = null;
            // $manifestPath = $extractPath . '/imsmanifest.xml';
            // if (file_exists($manifestPath)) {
            //     $xml = simplexml_load_file($manifestPath);
            //     if ($xml) {
            //         $resources = $xml->xpath('//resource[@type="webcontent"]');
            //         if (empty($resources)) {
            //             $resources = $xml->xpath('//resource');
            //         }
            //         if (isset($resources[0]['href'])) {
            //             $entryPoint = (string) $resources[0]['href'];
            //         }
            //     }
            // }
            // Log::info("Entry point found: " . ($entryPoint ?? 'Not found'));

            // // Step 5: Upload all extracted files to S3
            // $uploadedFiles = 0;
            // $failedFiles = 0;

            // try {
            //     $files = new RecursiveIteratorIterator(
            //         new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS),
            //         RecursiveIteratorIterator::LEAVES_ONLY
            //     );

            //     foreach ($files as $fileObj) {
            //         if ($fileObj->isFile()) {
            //             $fullPath = $fileObj->getRealPath();
            //             $relativePath = str_replace($extractPath, '', $fullPath);
            //             $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

            //             if (empty($relativePath)) {
            //                 continue;
            //             }

            //             $s3Path = "uploads/scorm_package/{$companyId}/{$slug}/{$relativePath}";

            //             try {
            //                 // Get the filename from the relative path
            //                 $fileName = basename($relativePath);
            //                 $directory = dirname($relativePath);

            //                 // Create a temporary uploaded file object for storeAs
            //                 $tempFile = new \Illuminate\Http\UploadedFile(
            //                     $fullPath,
            //                     $fileName,
            //                     mime_content_type($fullPath),
            //                     null,
            //                     true // Mark as test file to avoid validation
            //                 );

            //                 // Use storeAs method consistently with your app
            //                 $s3Directory = "uploads/scorm_package/{$companyId}/{$slug}";
            //                 if ($directory !== '.') {
            //                     $s3Directory .= "/{$directory}";
            //                 }

            //                 $result = $tempFile->storeAs($s3Directory, $fileName, 's3');

            //                 if ($result) {
            //                     $uploadedFiles++;
            //                     Log::info("Uploaded: $result");
            //                 } else {
            //                     Log::error("Failed to upload: $fullPath");
            //                     $failedFiles++;
            //                 }
            //             } catch (\Exception $uploadException) {
            //                 Log::error("Upload exception for $fullPath: " . $uploadException->getMessage());
            //                 $failedFiles++;
            //             }
            //         }
            //     }
            // } catch (\Exception $e) {
            //     Log::error("Error during file iteration: " . $e->getMessage());
            //     return response()->json(['error' => 'Error processing SCORM files: ' . $e->getMessage()], 500);
            // }

            // Log::info("Upload completed", [
            //     'uploaded_files' => $uploadedFiles,
            //     'failed_files' => $failedFiles
            // ]);

            // // Step 6: Clean up temp files
            // try {
            //     File::deleteDirectory($extractPath);
            //     if (file_exists($zipTempPath)) {
            //         unlink($zipTempPath);
            //     }
            //     Log::info("Temp files cleaned up");
            // } catch (\Exception $e) {
            //     Log::warning("Failed to clean up temp files: " . $e->getMessage());
            // }

            // // Step 7: Save to database
            // try {
            //     $scormTraining = ScormTraining::create([
            //         'name' => $request->name,
            //         'description' => $request->description,
            //         'company_id' => $companyId,
            //         'file_path' => "uploads/scorm_package/{$companyId}/{$slug}",
            //         'entry_point' => $request->entry_point ?? $entryPoint,
            //         'passing_score' => $request->passing_score,
            //     ]);

            //     Log::info("SCORM training saved to database", [
            //         'id' => $scormTraining->id,
            //         'name' => $scormTraining->name
            //     ]);

            //     return response()->json([
            //         'success' => true,
            //         'message' => 'SCORM package uploaded and saved successfully',
            //         'data' => [
            //             'id' => $scormTraining->id,
            //             'name' => $scormTraining->name,
            //             's3_folder' => "uploads/scorm_package/{$companyId}/{$slug}",
            //             'entry_point' => $entryPoint,
            //             'files_uploaded' => $uploadedFiles,
            //             'files_failed' => $failedFiles,
            //             'passing_score' => $request->passing_score
            //         ]
            //     ]);
            // } catch (\Exception $e) {
            //     Log::error("Failed to save SCORM training to database: " . $e->getMessage());
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Files uploaded but failed to save to database: ' . $e->getMessage()
            //     ], 500);
            // }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function testS3Simple()
    {
        try {
            // Simple S3 test
            $testKey = 'simple-test-' . time() . '.txt';
            $testContent = 'Simple S3 test content';

            $result = Storage::disk('s3')->put($testKey, $testContent);

            if ($result) {
                Storage::disk('s3')->delete($testKey);
                return response()->json([
                    'success' => true,
                    'message' => 'S3 connection working',
                    'bucket' => config('filesystems.disks.s3.bucket')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'S3 upload failed'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'S3 error: ' . $e->getMessage()
            ], 500);
        }
    }
}
