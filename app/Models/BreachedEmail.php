<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreachedEmail extends Model
{
    protected $fillable = ['email', 'data', 'company_id'];

    public function userData(){
        return $this->belongsTo(Users::class, 'email', 'user_email');
    }
}
