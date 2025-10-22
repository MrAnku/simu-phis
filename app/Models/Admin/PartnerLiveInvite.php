<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class PartnerLiveInvite extends Model
{
    protected $table = 'partner_live_invites';
    protected $fillable = [
        'admin',
        'invite_id',
        'program_name',
        'partner_email',
        'sent',
        'sent_at',
    ];
}
