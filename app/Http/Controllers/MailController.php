<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\CampaignMail;
use Illuminate\Support\Facades\Mail;
use Exception;

class MailController extends Controller
{
    public function sendMail($mailData)
    {
        // Set mail configuration dynamically
        config([
            'mail.mailers.smtp.host' => $mailData['sendMailHost'],
            'mail.mailers.smtp.username' => $mailData['sendMailUserName'],
            'mail.mailers.smtp.password' => $mailData['sendMailPassword'],
        ]);

        try {
            Mail::to($mailData['email'])->send(new CampaignMail($mailData));

            log_action("Email campaign mail sent", 'cronjob', 'cronjob');

            return response()->json(['success' => 'Great! Successfully sent your mail']);
        } catch (Exception $e) {

            log_action("Something went wrong while sending email campaign", 'cronjob', 'cronjob');
            return response()->json(['error' => 'Sorry! Please try again later', 'message' => $e->getMessage()], 500);
        }
    }
}
