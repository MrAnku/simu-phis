<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScormTraining extends Model
{
   protected $fillable = [
        'name',
        'description',
        'category',
        'file_path',
        'scorm_version', // Added scorm_version field
        'company_id',
        'passing_score',
        'entry_point'
   ];
}
