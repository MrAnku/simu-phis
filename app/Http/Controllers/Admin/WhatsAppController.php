<?php

namespace App\Http\Controllers\Admin;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\WhatsappNumChangeReq;
use Illuminate\Support\Facades\Mail;
use App\Mail\Admin\WhatsAppTokenUpdatedMail;

class WhatsAppController extends Controller
{
    public function index(){

        $requests = WhatsappNumChangeReq::with('partnerDetail')->get();

        // return $requests;
        return view('admin.whatsapp', compact('requests'));
    }

    public function approveNumberChange(Request $request){

        $id = $request->id;

        $req = WhatsappNumChangeReq::find($id);

        if($req){
            $this->createToken($request->token, $req->partner_id);
            $req->status = 1;
            $req->save();

            

            $partner = Partner::where('partner_id', $req->partner_id)->first();

            if($partner){
                Mail::to($partner->email)->send(new WhatsAppTokenUpdatedMail($partner));
            }

            return response()->json(['status'=>1, 'msg'=>'Token updated successfully!']);

        }else{
            return response()->json(['status'=>0, 'msg'=>'something went wrong!']);
        }

    }

    private function createToken($token, $partner_id){

        DB::table('partner_whatsapp_api')->where('partner_id', $partner_id)->delete();

        DB::table('partner_whatsapp_api')->insert([
            "partner_id" => $partner_id, 
           "token" => $token, 
           "created_at" => now()
        ]);

        return;

        
    }
}
