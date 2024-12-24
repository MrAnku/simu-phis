<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingModule extends Model
{
    use HasFactory;

    protected $table = 'training_modules';
    public $timestamps = false;

    protected $fillable = [
        'name', 
        'estimated_time', 
        'cover_image', 
        'passing_score',
        'category', 
        'json_quiz', 
        'module_language', 
        'company_id'
    ];
}
