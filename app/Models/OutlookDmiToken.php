<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutlookDmiToken extends Model
{
   protected $fillable = ['access_token', 'company_id'];
}
