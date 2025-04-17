<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TprmRequest extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'company_id',
        'partner_id',
        'status',
    ];
}
