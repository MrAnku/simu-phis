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
        'company_id',
        'passing_score',
        'entry_point'
   ];
}
