<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedPolicy extends Model
{
   protected $fillable = [
        'campaign_id',
        'user_name',
        'user_email',
        'policy',
        'accepted',
        'accepted_at',
        'json_quiz_response',
        'company_id',
    ];

    public function policyData(){
        return $this->belongsTo(Policy::class, 'policy', 'id');
    }
}
