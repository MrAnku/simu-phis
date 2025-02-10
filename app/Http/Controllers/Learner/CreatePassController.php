<?php

namespace App\Http\Controllers\Learner;

use App\Models\Users;
use Illuminate\Http\Request;
use App\Models\NewLearnerPassword;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

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
}
