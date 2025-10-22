<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class PartnerInvite extends Model
{
    protected $table = 'partner_invites';
    protected $fillable = [
        'admin',
        'invite_id',
        'program_name',
        'partner_emails',
        'status',
    ];

    public function inviteLive()
    {
        return $this->hasMany(PartnerLiveInvite::class, 'invite_id', 'invite_id');
    }
}
