<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inforgraphic extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'file_path',
        'company_id',
    ];
}
