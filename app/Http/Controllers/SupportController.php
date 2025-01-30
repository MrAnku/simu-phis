<?php

namespace App\Http\Controllers;

use App\Models\CpTickets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{
    public function index(){
        $company_id = Auth::user()->company_id;
        $closedTickets = CpTickets::with('conversations')->where('status', 0)->where('company_id', $company_id)->get();
        $openTickets = CpTickets::with('conversations')->where('status', 1)->where('company_id', $company_id)->get();
        return view('support', compact('closedTickets', 'openTickets'));
    }

    public function createTicket(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'sub' => 'required|string|max:255',
            'priority' => 'required|string',
            'msg' => 'required|string',
        ]);

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return redirect()->back()->withErrors(['error' => 'Invalid input detected.']);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        $name = $request->input('name');
        $email = $request->input('email');
        $sub = $request->input('sub');
        $priority = $request->input('priority');
        $msg = $request->input('msg');
        
        $ticket_no = rand(10000, 99999);
        $status = '1';
        $created_at = now();
        $company_id = Auth::user()->company_id;

        DB::table('cp_tkts')->insert([
            'name' => $name,
            'email' => $email,
            'priority' => $priority,
            'subject' => $sub,
            'msg' => $msg,
            'status' => $status,
            'cp_tkt_no' => $ticket_no,
            'company_id' => $company_id,
            'created_at' => $created_at,
        ]);

        DB::table('cp-tkts_conversations')->insert([
            'tkt_id' => $ticket_no,
            'person' => 'company',
            'msg' => $msg,
            'date' => $created_at,
            'company_id' => $company_id,
        ]);

        log_action("{$email} created support ticket");

        return redirect()->back()->with(['success' => 'Ticket created successfully']);
    }

    public function loadConversations(Request $request)
    {
        $request->validate([
            'tkt_id' => 'required|integer',
        ]);

        $tkt_id = $request->input('tkt_id');
        $company_id = Auth::user()->company_id;

        $conversations = DB::table('cp-tkts_conversations')
            ->where('company_id', $company_id)
            ->where('tkt_id', $tkt_id)
            ->get();

        return response()->json($conversations);
    }

    public function submitReply(Request $request)
    {
        $request->validate([
            'tkt_id' => 'required|integer',
            'msg' => 'required|string',
        ]);

        $input = $request->all();
        foreach ($input as $key => $value) {
            if (preg_match('/<[^>]*>|<\?php/', $value)) {
                return response()->json(['status'=> 0, 'msg' => 'Invalid input detected.']);
            }
        }
        array_walk_recursive($input, function (&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);

        $tkt_id = $request->input('tkt_id');
        $msg = $request->input('msg');
        $today = now();
        $company_id = Auth::user()->company_id;

        DB::table('cp-tkts_conversations')->insert([
            'tkt_id' => $tkt_id,
            'person' => 'company',
            'msg' => $msg,
            'date' => $today,
            'company_id' => $company_id,
        ]);

        log_action("Company replied in raised ticket");

        return response()->json(['status'=> 1, 'msg' => 'Reply submitted successfully']);
    }
}
