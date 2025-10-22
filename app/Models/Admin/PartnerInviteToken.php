<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class PartnerInviteToken extends Model
{
    protected $table = 'partner_invite_tokens';
    protected $fillable = [
        'invite_token',
        'partner_email',
        'expires_at',
    ];
}
