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
        'training_type',
        'core_behaviour',
        'content_type',
        'language',
        'security',
        'role',
        'duration',
        'tags',
        'program_resources',
        'estimated_time',
        'industry',
        'json_quiz',
        'module_language',
        'company_id',
        'alternative_training'
    ];

    public function trainingAssigned()
    {
        return $this->hasMany(TrainingAssignedUser::class, 'training', 'id');
    }
}
