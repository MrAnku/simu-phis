<?php

namespace App\Http\Controllers\Learner;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class LearnerAuthController extends Controller
{
    public function index(){
        if(!session('learner')){

            return view('learning.login');
        }else{
            return redirect()->route('learner.dashboard');
        }
    }

    public function login(Request $request){
        $email = $request->input('email');
        $password = $request->input('password');

        $user = DB::table('user_login')->where('login_username', $email)->first();

        if ($user) {
            if ($user->login_password == $password) {
                Session::put('learner', $user);
                return redirect()->route('learner.dashboard'); // Change this to your desired route
            } else {
                return back()->withErrors(['password' => 'Invalid Password']);
            }
        } else {
            return back()->withErrors(['email' => 'Invalid Credentials!']);
        }
    }
    public function logout(Request $request)
    {
        // Destroy the session
        $request->session()->forget('learner');
        return redirect()->route('learner.loginPage');
    }
}
