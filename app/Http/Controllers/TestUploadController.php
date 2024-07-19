<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestUploadController extends Controller
{
    public function showUploadForm()
    {
        return view('upload_form');
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf',
        ]);

        $file = $request->file('file');
        $filePath = 'uploads/' . $file->getClientOriginalName();

        // Upload file to S3
        Storage::disk('s3')->put($filePath, file_get_contents($file));

        // Get CloudFront URL
        $url = Storage::disk('s3')->url($filePath);

        return response()->json(['url' => $url]);
    }
}
