<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\ComicQueue;
use COM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ComicsController extends Controller
{
    public function index()
    {
        $comics = Comic::where('company_id', Auth::user()->company_id)->get();
        return response()->json([
            'success' => true,
            'message' => 'Comics retrieved successfully',
            'data' => $comics
        ]);
    }

    public function generateComic(Request $request)
    {
        try {
            
            $request->validate([
                'topic' => 'required|string|max:255',
            ]);
            $companyId = Auth::user()->company_id;

            $response = Http::post('http://91.98.162.246:5555/generate_comic', [
                'topic' => $request->input('topic'),
                'company_id' => $companyId,
            ]);
            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to generate comics') . $response->body(),
                ], 500);
            }
            $taskId = $response->json()['task_id'];
            ComicQueue::create([
                'topic' => $request->input('topic'),
                'task_id' => $taskId,
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Comic generation initiated successfully'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function comicsInQueue(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $comicsInQueue = ComicQueue::where('company_id', $companyId)
                ->get();
            return response()->json([
                'success' => true,
                'message' => __('Comics in queue retrieved successfully'),
                'data' => $comicsInQueue,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }


    public function saveComic(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|string|max:255',
                'file_path' => 'required|string|max:255',
            ]);
            $companyId = Auth::user()->company_id;

            $comic = new Comic();
            $comic->name = $request->input('name');
            $comic->description = $request->input('description');
            $comic->category = $request->input('category');
            $comic->file_path = $request->input('file_path');
            $comic->company_id = $companyId;
            $comic->save();
            return response()->json([
                'success' => true,
                'message' => __('Comic saved successfully'),
                'data' => $comic,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }
    public function deleteComic($encodedId = null)
    {
        try {
            if (!$encodedId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Comic ID is required'),
                ], 400);
            }
            $comicId = base64_decode($encodedId);
            $comic = Comic::where('id', $comicId)
                ->where('company_id', Auth::user()->company_id)
                ->first();
            if (!$comic) {
                return response()->json([
                    'success' => false,
                    'message' => __('Comic not found'),
                ], 404);
            }
            //delete from s3
            Storage::disk('s3')->delete(ltrim($comic->file_path, '/'));
            $comic->delete();
            return response()->json([
                'success' => true,
                'message' => __('Comic deleted successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }
}
