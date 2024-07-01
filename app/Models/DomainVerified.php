<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainVerified extends Model
{
    use HasFactory;

    protected $table = 'verified_domains';
    public $timestamps = false;

    protected $fillable = [
        'domain',
        'temp_code',
        'verified',
        'company_id',
    ];
}
