<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslatedTraining extends Model
{
    protected $fillable = ['training_id', 'language', 'json_quiz'];
}
