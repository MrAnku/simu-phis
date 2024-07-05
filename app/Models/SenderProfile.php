<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SenderProfile extends Model
{
    use HasFactory;

    protected $table = 'senderprofile';
    public $timestamps = false;

    protected $fillable = [
        'profile_name',
        'from_name',
        'from_email',
        'host',
        'username',
        'password',
        'company_id'
    ];


    public function phishingEmail()
    {
        return $this->belongsTo(PhishingEmail::class, 'senderProfile', 'id');
    }
}
