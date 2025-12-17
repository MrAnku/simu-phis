<?php

namespace App\Services;

use App\Models\TrainingSetting;

class TrainingSettingService
{
    public function checkDisableOverdueTraining(string $companyId): bool
    {
        return TrainingSetting::where('company_id', $companyId)->value('disable_overdue_training');
    }
}
