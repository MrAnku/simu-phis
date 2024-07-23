<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpTicketsConversation extends Model
{
    use HasFactory;

    protected $table = 'cp-tkts_conversations';
    public $timestamps = false;
}
