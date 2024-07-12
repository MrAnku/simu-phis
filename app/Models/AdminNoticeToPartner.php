<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNoticeToPartner extends Model
{
    use HasFactory;

    protected $table = 'admin_notice_to_partner';
    public $timestamps = false;

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'partner_id');
    }
}
