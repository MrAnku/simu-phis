<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingAssignedUser extends Model
{
    use HasFactory;

    protected $table = 'training_assigned_users';

    protected $fillable = [
        'campaign_id',
        'training',
        'user_id',
        'user_name',
        'user_email',
        'training_due_date',
        'training_lang',
        'training_type',
        'assigned_date',
        'completion_date',
        'personal_best',
        'completed',
        'certificate_id',
        'company_id',
        'last_reminder_date',
        'training_started',
        'grade',
        'badge',
        'certificate_path',
        'feedback',
        'alt_training',
        'survey_response'
    ];

    protected $casts = [
    'survey_response' => 'array'
      ];


    public function trainingData()
    {
        return $this->belongsTo(TrainingModule::class, 'training', 'id');
    }

    public function trainingGame()
    {
        return $this->belongsTo(TrainingGame::class, 'training', 'id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }
}
