<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionDb extends Model
{
    use HasFactory;
     
    protected $fillable = [
        'region_name',
        'db_host',
        'db_port',
        'db_database',
        'db_username',
    ];
}