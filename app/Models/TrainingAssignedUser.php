<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingAssignedUser extends Model
{
    use HasFactory;

    protected $table = 'training_assigned_users';

    protected $fillable = [
        'training',
        'user_name',
        'user_email',
        'completion_date',
        'personal_best',
        'completed',
        'certificate_id',
        'last_reminder_date',
        // Add other columns that should be mass assignable
    ];

    public function trainingData(){
        return $this->belongsTo(TrainingModule::class, 'training', 'id');
    }

    public function trainingGame(){
        return $this->belongsTo(TrainingGame::class, 'training', 'id');
    }

    public function campaign(){
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }
}
