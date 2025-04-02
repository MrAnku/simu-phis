<?php

namespace App\Http\Controllers\Learner;

use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\NewLearnerPassword;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Company;

class CreatePassController extends Controller
{
    public function createPasswordPage($token){
        $passlinktoken = NewLearnerPassword::where('token', $token)
        ->where('created', 0)
        ->first();
        if(!$passlinktoken){
            echo "Link Expired";
            return;
        }
        if($passlinktoken->reset == 1){
            if($passlinktoken->created_at < now()->subMinutes(10)){
                echo 'Link Expired';
                $passlinktoken->delete();
                return;
            }
        }
        $route = '/learner/create-password';
        return view('learning.createpass', ['token' => $token, 'route' => $route]);
    }

    public function storePassword(Request $request){
        //xss check start
        
        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->with('error', 'Invalid Input Detected');
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        //xss check end

        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $passlinktoken = NewLearnerPassword::where('token', $request->token)
        ->where('created', 0)
        ->first();
        if(!$passlinktoken){
            abort(404);
        }

        $user = Users::where('user_email', $passlinktoken->email)->first();
        if(!$user){
            abort(404);
        }
        
        DB::table('user_login')->insert([
            'user_id' => $user->id,
            'login_username' => $user->user_email,
            'login_password' => $request->password,
        ]);

        $passlinktoken->created = 1;
        $passlinktoken->save();

        return redirect()->to(env('SIMUPHISH_LEARNING_URL'));
    }

    public function createCompanyPassPage(Request $request){

        //has valid token
        $token = $request->route('token');
        $validToken = Company::where('pass_create_token', $token)->where('password', null)->first();
        if(!$validToken){
            return "Invalid Token or Token Expired";
        }
        return view('auth.company-pass-create', [
            'token' => $token,
            'company_id' => encrypt($validToken->id)
        ]);

    }

    public function storeCompanyPass(Request $request){
        $request->validate([
            'password' => [
                'required',
                'min:8',
                'regex:/[A-Z]/', // Must contain at least one uppercase letter
                'regex:/[a-z]/', // Must contain at least one lowercase letter
                'regex:/[0-9]/', // Must contain at least one digit
                'regex:/[@$!%*?&#]/', // Must contain at least one special character
                'confirmed', // Must match the password confirmation field
            ],
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one digit, and one special character.',
        ]);
        //xss check start
        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->with('error', 'Invalid Input Detected');
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);
        //xss check end
        

        $companyId = decrypt($request->cid);
        $company = Company::find($companyId);

        if (!$company || $company->pass_create_token !== $request->tkn) {
            return redirect()->back()->with('error', 'Invalid Token');
        }

        $company->password = bcrypt($request->password);
        $company->pass_create_token = null; // Invalidate the token
        $company->save();

        return redirect()->route('login')->with('success', 'Password created successfully');
    }
}
