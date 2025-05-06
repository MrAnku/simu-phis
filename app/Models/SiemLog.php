<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiemLog extends Model
{
    protected $fillable = ['log_msg', 'synced_at', 'company_id'];

}
