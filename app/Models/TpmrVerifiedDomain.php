<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TpmrVerifiedDomain extends Model
{
    use HasFactory;

    protected $table = 'tpmrverifieddomain';  // Table name
    public $timestamps = false;  // If you're using timestamps (created_at, updated_at)

    protected $fillable = [
        'domain',
        'temp_code',
        'verified',
        'company_id',
        'partner_id'
    ];
}
