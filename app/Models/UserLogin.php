<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLogin extends Model
{
    use HasFactory;

    protected $table = 'user_login';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'login_username',
        'login_password',
        'token',
        'token_expiry'
    ];
}
