<?php

namespace App\Http\Controllers\Learner;

use App\Models\Users;
use App\Models\UserLogin;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\LearnerPassReset;
use App\Models\NewLearnerPassword;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Password;

class LearnerAuthController extends Controller
{
    public function index()
    {
        if (!session('learner')) {

            return view('learning.login');
        } else {
            return redirect()->route('learner.dashboard');
        }
    }

    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $user = DB::table('user_login')->where('login_username', $email)->first();

        if ($user) {
            if ($user->login_password == $password) {
                Session::put('learner', $user);
                log_action("Learner {$email} logged in", 'learner', 'learner');
                return redirect()->route('learner.dashboard'); // Change this to your desired route
            } else {
                return back()->withErrors(['password' => 'Invalid Password']);
            }
        } else {
            return back()->withErrors(['email' => 'Invalid Credentials!']);
        }
    }
    public function loginWithoutPassword(Request $request)
    {
        $email = $request->input('email');

        // Fetch the token and expiry time from learnerloginsession
        $session = DB::table('learnerloginsession')
            ->where('email', $email)
            ->orderBy('created_at', 'desc') // Ensure the latest session is checked
            ->first();

        // Check if session exists and if the token is expired
        if (!$session || now()->greaterThan(Carbon::parse($session->expiry))) {
            return back()->withErrors(['email' => 'Invalid Credentials!']);
        }


        // Fetch user details from user_login table
        $user = DB::table('user_login')->where('login_username', $email)->first();

        if ($user) {
            Session::put('learner', $user);
            log_action("Learner {$email} logged in", 'learner', 'learner');
            return redirect()->route('learner.dashboard'); // Redirect to learner dashboard
        } else {
            return back()->withErrors(['email' => 'Invalid Credentials!']);
        }
    }

    public function logout(Request $request)
    {
        // Destroy the session
        $request->session()->forget('learner');
        log_action("Learner logged out", 'learner', 'learner');
        return redirect()->route('learner.loginPage');
    }

    public function forgotPass()
    {
        return view('learning.forgot-pass');
    }

    public function forgotPassStore(Request $request)
    {
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

        $email = $request->input('email');
        $user = Users::where('user_email', $email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Employee not found']);
        }
        $userLogin = UserLogin::where('login_username', $email)->first();
        $token = Str::random(20);
        if (!$userLogin) {
            UserLogin::create([
                'user_id' => $user->id,
                'login_username' => $email,
                'login_password' => null,
                'token' => $token,
                'token_expiry' => now()->addMinutes(10)
            ]);
        }
        UserLogin::where('login_username', $email)->update([
            'token' => $token,
            'token_expiry' => now()->addMinutes(10)
        ]);

        $passwordGenLink = url('/create-password', ['token' => $token]);

        Mail::to($email)->send(new LearnerPassReset($user, $passwordGenLink));

        return redirect()->back()->with('success', 'Resent link sent. Link will expire in 10 minutes');

        // $user = DB::table('user_login')->where('login_username', $email)->first();
        // if(!$user){
        //     return back()->withErrors(['email' => 'Employee not found']);
        // }

        // $token = encrypt($user->login_username);

        // $passwordGenLink = env('APP_URL') . '/learner/create-password/' . $token;

        // $alreadySent = NewLearnerPassword::where('email', $user->login_username)
        // ->where('created', 0)
        // ->where('created_at', '>=', now()->subMinutes(10))
        // ->latest()
        // ->first();
        // if($alreadySent){
        //     return back()->withErrors(['email' => 'Password reset link already sent']);
        // }

        // Mail::to($user->login_username)->send(new LearnerPassReset($user, $passwordGenLink));

        // NewLearnerPassword::create([
        //     'email' => $user->login_username,
        //     'token' => $token,
        //     'reset' => 1,
        //   ]);

        //   return redirect()->back()->with('success', 'Resent link sent. Link will expire in 10 minutes');

    }

    public function createPassPage($token)
    {
        $validToken = UserLogin::where('token', $token)->first();
        if (!$validToken) {
            echo 'Invalid Token';
            return;
        }
        if ($validToken->token_expiry < now()) {
            echo 'Link Expired';
            return;
        }

        $route = '/create-password/store';

        return view('learning.createpass', ['token' => $token, 'route' => $route]);
    }
    public function storePassword(Request $request)
    {
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

        $passlinktoken = UserLogin::where('token', $request->token)
            ->first();
        if (!$passlinktoken) {
            abort(404);
        }

        UserLogin::where('token', $request->token)->update([
            'login_password' => $request->password,
            'token' => null,
            'token_expiry' => null
        ]);

        return view('learning.pass-reset-thankyou');


        // return redirect()->to(env('SIMUPHISH_LEARNING_URL'));
    }
}
