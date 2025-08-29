<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
  protected $fillable = ['message', 'is_read', 'sent_by', 'partner_id', 'company_id'];
}
