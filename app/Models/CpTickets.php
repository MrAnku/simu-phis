<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpTickets extends Model
{
    use HasFactory;

    protected $table = 'cp_tkts';
    public $timestamps = false;

    public function conversations(){
        return $this->hasMany(CpTicketsConversation::class, 'tkt_id', 'cp_tkt_no');
    }
}
