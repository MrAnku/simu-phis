<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{

    protected $table = 'medias';

    protected $fillable = [
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'company_id',
    ];
}
