<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlueCollarTrainingUser extends Model
{
    use HasFactory;

    protected $table = 'blue_collar_training_users';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'user_name',
        'user_whatsapp',
        'training',
        'training_lang',
        'training_type',
        'personal_best',
        'completed',
        'assigned_date',
        'training_due_date',
        'completion_date',
        'company_id',
        'certificate_id',
        'training_started',
        'grade',
    ];

    public function trainingModule()
    {
        return $this->hasOne(TrainingModule::class, 'id', 'training');
    }
    public function trainingData()
    {
        return $this->hasOne(TrainingModule::class, 'id', 'training');
    }
}
