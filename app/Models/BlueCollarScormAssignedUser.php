<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlueCollarScormAssignedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'user_id',
        'user_name',
        'user_whatsapp',
        'scorm',
        'scorm_started',
        'personal_best',
        'grade',
        'completed',
        'assigned_date',
        'scorm_due_date',
        'completion_date',
        'company_id',
        'certificate_id',
        'last_reminder_date',
        'badge',
        'certificate_path',
        'feedback',
        'survey_response',
    ];

    public function scormTrainingData()
    {
        return $this->belongsTo(ScormTraining::class, 'scorm', 'id');
    }
}
