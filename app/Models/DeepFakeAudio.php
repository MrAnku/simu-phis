<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeepFakeAudio extends Model
{

    protected $fillable = [
        'audio_id',
        'name',
        'gender',
        'language',
        'use_case',
        'company_id',
    ];
}
