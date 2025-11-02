<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComicAssignedUser extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'user_name',
        'user_email',
        'comic',
        'assigned_at',
        'seen_at',
        'company_id',
    ];
}
