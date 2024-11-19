<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappNumChangeReq extends Model
{

    use HasFactory;
    protected $table = 'whatsapp_num_change_req';

    public function partnerDetail(){
        return $this->hasOne(Partner::class, 'partner_id', 'partner_id');
    }
}
