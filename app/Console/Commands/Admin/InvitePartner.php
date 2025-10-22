<?php

namespace App\Console\Commands\Admin;

use App\Models\Partner;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use App\Models\Admin\PartnerInvite;
use Illuminate\Support\Facades\Mail;
use App\Mail\Admin\SendPartnerInvite;
use App\Models\Admin\PartnerInviteToken;

class InvitePartner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:invite-partner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->sendPartnerInvites();
        $this->checkInvitesStatus();
    }

    private function sendPartnerInvites()
    {
        $invites = PartnerInvite::with('inviteLive')->where('status', 'pending')->get();

        foreach ($invites as $invite) {
            $targetPartners = $invite->inviteLive->where('sent', false)->take(3);
            foreach ($targetPartners as $targetPartner) {
                try {

                    $token = Str::random(32);

                    Mail::to($targetPartner->partner_email)->send(new SendPartnerInvite($targetPartner->partner_email, $token));

                    PartnerInviteToken::create([
                        'invite_token' => $token,
                        'partner_email' => $targetPartner->partner_email,
                        'expires_at' => now()->addDays(2),
                    ]);
                    $targetPartner->sent = true;
                    $targetPartner->sent_at = now();
                    $targetPartner->save();

                    $this->info("Sent invite to: " . $targetPartner->partner_email);
                } catch (\Exception $e) {
                    $this->error("Failed to send invite to: " . $targetPartner->partner_email . ". Error: " . $e->getMessage());
                    continue;
                }
            }
        }
    }

    private function checkInvitesStatus()
    {
        $invites = PartnerInvite::with('inviteLive')->where('status', 'pending')->get();

        foreach ($invites as $invite) {
            $allSent = $invite->inviteLive->every(function ($liveInvite) {
                return $liveInvite->sent;
            });

            if ($allSent) {
                $invite->status = 'completed';
                $invite->save();
                $this->info("Invite ID " . $invite->invite_id . " marked as completed.");
            }
        }
    }
}
