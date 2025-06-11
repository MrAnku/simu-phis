<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedPolicy extends Model
{
   protected $fillable = [
        'user_name',
        'user_email',
        'policy',
        'accepted',
        'accepted_at',
        'company_id',
    ];
}
