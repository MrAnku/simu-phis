<?php

namespace App\Http\Controllers\Admin;

use App\Models\Encyclopedia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class EncycloController extends Controller
{
   public function index()
   {
        $data = Encyclopedia::all();

       return view('admin.encyclo', compact('data'));
   }

   public function store(Request $request){
         $request->validate([
              'title' => 'required',
              'content' => 'nullable',
              'featured' => '',
              'file' => 'nullable|mimes:pdf,zip|max:5125',
         ]);
    
         if ($request->hasFile('file')) {
             $filePath = $request->file('file')->storeAs('encyclopedia', $request->file('file')->getClientOriginalName(), 's3');
             $fileUrl = Storage::disk('s3')->url($filePath);
         } else {
             $fileUrl = null;
         }

    
         Encyclopedia::create([
              'title' => $request->title,
              'content' => $request->content ?? null,
              'file' => $fileUrl ?? null,
              'featured' => $request->featured
         ]);
    
         return redirect()->back()->with('success', 'Data added successfully');
   }

   public function deleteData(Request $request){
        $id = $request->id;
        $data = Encyclopedia::find($id);
        if ($data->file) {
            $filePath = parse_url($data->file, PHP_URL_PATH);
            Storage::disk('s3')->delete($filePath);
        }
        $data->delete();
        return response()->json(['status' => 1, 'msg' => 'Data deleted successfully']);
   }

   public function getEncyclo($id){
        $data = Encyclopedia::find($id);
        return response()->json($data);
   }

   public function updateEncyclo(Request $request){
        $request->validate([
            'title' => 'required',
            'content' => 'nullable',
            'file' => 'nullable|mimes:pdf,zip|max:5125',
        ]);

        $data = Encyclopedia::find($request->encyclo_id);
        if ($request->hasFile('file')) {
            if ($data->file) {
                $filePath = parse_url($data->file, PHP_URL_PATH);
                Storage::disk('s3')->delete($filePath);
            }
            $filePath = $request->file('file')->storeAs('encyclopedia', $request->file('file')->getClientOriginalName(), 's3');
            $fileUrl = Storage::disk('s3')->url($filePath);
        } else {
            $fileUrl = $data->file;
        }

        $data->update([
            'title' => $request->title,
            'content' => $request->content ?? null,
            'file' => $fileUrl ?? null,
            'featured' => $request->featured
        ]);

        return redirect()->back()->with('success', 'Data updated successfully');

   }
}
