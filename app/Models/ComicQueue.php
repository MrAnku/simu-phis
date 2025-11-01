<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComicQueue extends Model
{
    protected $fillable = [
        'topic',
        'task_id',
        'comic_url',
        'status',
        'company_id',
    ];
}
