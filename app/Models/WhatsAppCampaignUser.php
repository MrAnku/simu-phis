<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppCampaignUser extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'whatsapp_camp_users';

    // Define the fillable fields for mass assignment
    protected $fillable = [
        'camp_id',
        'camp_name',
        'user_group',
        'user_name',
        'user_id',
        'user_email',
        'user_whatsapp',
        'template_name',
        'template_language',
        'training',
        'training_assigned',
        'components',
        'status',
        'link_clicked',
        'emp_compromised',
        'created_at',
        'company_id'
    ];

    // Optionally, if you don’t want to use `created_at` and `updated_at` timestamps,
    // you can disable them by setting the `$timestamps` property to false.
    public $timestamps = false;

    public function trainingAsssigned(){
        return $this->belongsTo(TrainingAssignedUser::class, 'campaign_id', 'camp_id');
    }

    public function trainingData(){
        return $this->belongsTo(TrainingModule::class, 'training', 'id');
    }
}
