<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingAssignedUser extends Model
{
    use HasFactory;

    protected $table = 'training_assigned_users';
    public $timestamps = false;

    protected $fillable = [
        'training',
        'username',
        'completion_date',
        'personal_best',
        'completed',
        'certificate_id',
        'last_reminder_date',
        // Add other columns that should be mass assignable
    ];

    public function trainingData(){
        return $this->hasOne(TrainingModule::class, 'id', 'training');
    }

    public function trainingGame(){
        return $this->hasOne(TrainingGame::class, 'id', 'training');
    }
}
