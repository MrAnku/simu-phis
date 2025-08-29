<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyTrigger extends Model
{
    public function training()
    {
        return $this->belongsTo(TrainingModule::class, 'training')->select('id', 'name');
    }

    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy')->select('id', 'policy_name');
    }
}
