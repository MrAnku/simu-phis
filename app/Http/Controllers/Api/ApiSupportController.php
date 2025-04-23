<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CpTickets;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ApiSupportController extends Controller
{
    public function index()
    {
        try {
            $company_id = Auth::user()->company_id;

            $closedTickets = CpTickets::with('conversations')
                ->where('status', 0)
                ->where('company_id', $company_id)
                ->get();

            $openTickets = CpTickets::with('conversations')
                ->where('status', 1)
                ->where('company_id', $company_id)
                ->get();

            return response()->json([
                'success' => true,
                'message' => __('Tickets fetched successfully.'),
                'data' => [
                    'closedTickets' => $closedTickets,
                    'openTickets' => $openTickets
                ]
            ], 200);
        } catch (\Exception $e) {
            log_action("Error fetching tickets: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch tickets.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function createTicket(Request $request)
    {
        try {
            // Validate incoming data
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'sub' => 'required|string|max:255',
                'priority' => 'required|string',
                'msg' => 'required|string',
            ]);

            // XSS sanitization
            foreach ($validated as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Invalid input detected.')
                    ], 422);
                }
            }

            array_walk_recursive($validated, function (&$value) {
                $value = strip_tags($value);
            });

            $ticket_no = rand(10000, 99999);
            $created_at = now();
            $company_id = Auth::user()->company_id;

            // Insert into tickets table
            DB::table('cp_tkts')->insert([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'priority' => $validated['priority'],
                'subject' => $validated['sub'],
                'msg' => $validated['msg'],
                'success' => true,
                'cp_tkt_no' => $ticket_no,
                'company_id' => $company_id,
                'created_at' => $created_at,
            ]);

            // Insert initial conversation
            DB::table('cp-tkts_conversations')->insert([
                'tkt_id' => $ticket_no,
                'person' => 'company',
                'msg' => $validated['msg'],
                'date' => $created_at,
                'company_id' => $company_id,
            ]);

            log_action("{$validated['email']} created support ticket");

            return response()->json([
                'success' => true,
                'message' => __('Ticket created successfully.'),
                'ticket_no' => $ticket_no
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') .  $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            log_action("Error creating ticket: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function loadConversations(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'tkt_id' => 'required|integer',
            ]);

            $tkt_id = $validated['tkt_id'];
            $company_id = Auth::user()->company_id;

            // Fetch conversations ordered by latest
            $conversations = DB::table('cp-tkts_conversations')
                ->where('company_id', $company_id)
                ->where('tkt_id', $tkt_id)
                ->orderBy('date', 'asc') // Or 'desc' depending on your frontend needs
                ->get();

            return response()->json([
                'success' => true,
                'message' => __('Conversations fetched successfully.'),
                'data' => $conversations
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error.'),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            log_action("Error loading conversations: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Something went wrong.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function submitReply(Request $request)
    {
        try {
            // Validate input
            $input = $request->validate([
                'tkt_id' => 'required|integer',
                'msg' => 'required|string',
            ]);

            // Security check to prevent HTML/PHP injection (already sanitizing input)
            foreach ($input as $key => $value) {
                if (preg_match('/<[^>]*>|<\?php/', $value)) {
                    return response()->json([
                        'success' => false,
                        'msg' => __('Invalid input detected.')
                    ], 400);
                }
                // Strip HTML tags
                $input[$key] = strip_tags($value);
            }

            // Insert reply into the conversations table
            DB::table('cp-tkts_conversations')->insert([
                'tkt_id' => $input['tkt_id'],
                'person' => 'company',
                'msg' => $input['msg'],
                'date' => now(),
                'company_id' => Auth::user()->company_id,
            ]);

            log_action("Company replied in raised ticket: " . $input['msg']);

            return response()->json([
                'success' => true,
                'message' => __('Reply submitted successfully'),
            ], 200);
        } catch (\Exception $e) {
            // Log the error message for debugging
            log_action("Error submitting reply: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => __('Error: ') . $e->getMessage()
            ], 500);
        }
    }




    // public function submitReply(Request $request)
    // {
    //     $request->validate([
    //         'tkt_id' => 'required|integer',
    //         'msg' => 'required|string',
    //     ]);

    //     $input = $request->all();
    //     foreach ($input as $key => $value) {
    //         if (preg_match('/<[^>]*>|<\?php/', $value)) {
    //             return response()->json(['success' => false, 'msg' => __('Invalid input detected.')]);
    //         }
    //     }
    //     array_walk_recursive($input, function (&$input) {
    //         $input = strip_tags($input);
    //     });
    //     $request->merge($input);

    //     $tkt_id = $request->input('tkt_id');
    //     $msg = $request->input('msg');
    //     $today = now();
    //     $company_id = Auth::user()->company_id;

    //     DB::table('cp-tkts_conversations')->insert([
    //         'tkt_id' => $tkt_id,
    //         'person' => 'company',
    //         'msg' => $msg,
    //         'date' => $today,
    //         'company_id' => $company_id,
    //     ]);

    //     log_action("Company replied in raised ticket");

    //     return response()->json(['success' => true, 'msg' => __('Reply submitted successfully')]);
    // }
}
