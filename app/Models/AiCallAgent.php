<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCallAgent extends Model
{
    protected $fillable = ['company_id', 'agent_id', 'request_id', 'agent_name'];
}
