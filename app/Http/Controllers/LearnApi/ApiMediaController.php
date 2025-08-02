<?php

namespace App\Http\Controllers\LearnApi;

use App\Models\Media;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApiMediaController extends Controller
{
     public function uploadFile(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'file' => 'required|file|mimes:jpg,jpeg,png,webp,zip,ppt,pptx,mp4,mp3,pdf,avif|max:20480', // 20 MB max
            ]);

            $file = $request->file('file');

            $mimeType = $file->getMimeType();

            // Determine the file category
            $category = match (true) {
                str_starts_with($mimeType, 'image/') => 'image',
                str_starts_with($mimeType, 'video/') => 'video',
                str_starts_with($mimeType, 'zip/') => 'zip',
                str_starts_with($mimeType, 'ppt/') => 'ppt',
                str_starts_with($mimeType, 'video/mp4/') => 'video',
                str_starts_with($mimeType, 'audio/mpeg/') => 'mp3',
                $mimeType === 'application/pdf' => 'pdf',
                in_array($mimeType, [
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ]) => 'ppt',
                default => 'assets',
            };

            // Generate a random name for the file
            $extension = $file->getClientOriginalExtension();
            $filename = $file->getClientOriginalName();

            $sizeInBytes = $file->getSize();
            $sizeInKB = $sizeInBytes / 1024;

            // Define custom limits per file type (in Kb)
            $sizeLimits = [
                'pdf' => 10 * 1024,
                'zip' => 10 * 1024,
                'ppt' => 5 * 1024,
                'mp4' => 200 * 1024,
                'mp3' => 2 * 1024,
                'jpg' => 3 * 1024,
                'jpeg' => 5 * 1024,
                'png' => 5 * 1024,
                'webp' => 5 * 1024,
            ];

            $extensionLower = strtolower($extension);

            if (isset($sizeLimits[$extensionLower]) && $sizeInKB > $sizeLimits[$extensionLower]) {
                return response()->json([
                    'error' => 'The uploaded ' . $extensionLower . ' file exceeds the allowed limit of ' . $sizeLimits[$extensionLower] . ' MB.'
                ], 422);
            }
            $filePath = Storage::disk('s3')->putFileAs("media/{$category}", $file, $filename);

            Media::create([
                'company_id' => Auth::user()->company_id,
                'file_path' => '/' . $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $sizeInKB,
                'file_type' => $file->getClientMimeType(),
            ]);

            log_action("File uploaded");
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully.'
            ], 201);
        } catch (ValidationException $e) {
            // Handle the validation exception
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }

    public function fetchFiles()
    {
        try {
            // Fetch all media files for the authenticated company
            $mediaFiles = Media::where('company_id', Auth::user()->company_id)->get();

            // Return the media files as a JSON response
            return response()->json(['success' => true, 'data' => $mediaFiles, 'msg' => 'All Uploaded files fetched successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Error: ') . $e->getMessage()], 500);
        }
    }
}
