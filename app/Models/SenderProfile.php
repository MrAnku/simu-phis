<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SenderProfile extends Model
{
    use HasFactory;

    protected $table = 'senderprofile';
    public $timestamps = false;
}
