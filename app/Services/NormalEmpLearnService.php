<?php

namespace App\Services;

use App\Mail\TrainingCompleteMail;
use App\Models\Badge;
use App\Models\CampaignLive;
use App\Models\CertificateTemplate;
use App\Models\QuishingLiveCamp;
use App\Models\ScormAssignedUser;
use App\Models\TprmCampaignLive;
use App\Models\TrainingAssignedUser;
use App\Models\Users;
use App\Models\WaLiveCampaign;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class NormalEmpLearnService
{
    public function calculateRiskLevel($riskScore)
    {
        $riskScoreRanges = [
            'poor' => [0, 20],
            'fair' => [21, 40],
            'good' => [41, 60],
            'veryGood' => [61, 80],
            'excellent' => [81, 100],
        ];

        foreach ($riskScoreRanges as $label => [$min, $max]) {
            if ($riskScore >= $min && $riskScore <= $max) {
                $riskLevel = $label;
                break;
            }
        }
        return $riskLevel;
    }

    public function calculateLeaderboardRank($email)
    {

        $companyId = Users::where('user_email', $email)->value('company_id');

        $trainingUsers = TrainingAssignedUser::where('company_id', $companyId)->get();
        $scormUsers = ScormAssignedUser::where('company_id', $companyId)->get();

        $allUsers = $trainingUsers->merge($scormUsers);

        $currentUserEmail = $email;

        $grouped = $allUsers->groupBy('user_email')->map(function ($group, $email) use ($currentUserEmail) {
            $average = $group->avg('personal_best');
            $assignedTrainingsCount = $group->count();

            return [
                'email' => $email,
                'name' => strtolower($email) == strtolower($currentUserEmail) ? 'You' : ($group->first()->user_name ?? 'N/A'),
                'average_score' => round($average, 2),
                'assigned_trainings_count' => $assignedTrainingsCount,
            ];
        })->filter(function ($user) {
            return $user['average_score'] >= 10; // Filter users with score >= 10
        })->sortByDesc('average_score')->values();


        // Add leaderboard rank
        $leaderboard = $grouped->map(function ($user, $index) {
            $user['leaderboard_rank'] = $index + 1;
            return $user;
        });

        $currentUserRank = optional($leaderboard->firstWhere('email', $currentUserEmail))['leaderboard_rank'] ?? null;
        return [
            'leaderboard' => $leaderboard,
            'current_user_rank' => $currentUserRank,
        ];
    }

    public function getAllEarnedBadges($email)
    {
        $allBadgeIds = [];

        // Collect badge IDs from training
        $trainingWithBadges = TrainingAssignedUser::where('user_email', $email)
            ->whereNotNull('badge')
            ->get();

        foreach ($trainingWithBadges as $training) {
            $badgeIds = json_decode($training->badge, true) ?? [];
            $allBadgeIds = array_merge($allBadgeIds, $badgeIds);
        }

        // Collect badge IDs from SCORM
        $scormWithBadges = ScormAssignedUser::where('user_email', $email)
            ->whereNotNull('badge')
            ->get();

        foreach ($scormWithBadges as $scorm) {
            $badgeIds = json_decode($scorm->badge, true) ?? [];
            $allBadgeIds = array_merge($allBadgeIds, $badgeIds);
        }

        // Remove duplicate badge IDs
        $uniqueBadgeIds = array_unique($allBadgeIds);

        // Fetch badges
        $badges = Badge::whereIn('id', $uniqueBadgeIds)->get();

        return [
            'badges' => $badges,
            'total_badges' => count($badges),
            'total_unlock_badges' => Badge::where('id', '!=', $uniqueBadgeIds)->count()
        ];
    }

    public function getAllProgressTrainings($email)
    {
        $allAssignedTrainingMods = TrainingAssignedUser::where('user_email', $email)->get();

        $allAssignedScorms = ScormAssignedUser::where('user_email', $email)->get();

        $allAssignedTrainings = [];
        foreach ($allAssignedTrainingMods as $trainingMod) {
            $allAssignedTrainings[] = [
                'training_name' => $trainingMod->training_type == 'games' ? $trainingMod->trainingGame->name : $trainingMod->trainingData->name,
                'score' => $trainingMod->personal_best,
                'status' => $trainingMod->completed ? 'Completed' : 'Not Completed',
                'training_due_date' => $trainingMod->training_due_date,
                'completion_date' => $trainingMod->completion_date,
                'training_type' => $trainingMod->training_type,
            ];
        }

        foreach ($allAssignedScorms as $scorm) {
            $allAssignedTrainings[] = [
                'training_name' => $scorm->scormTrainingData->name,
                'score' => $scorm->personal_best,
                'status' => $scorm->completed ? 'Completed' : 'Not Completed',
                'training_due_date' => $scorm->scorm_due_date,
                'completion_date' => $scorm->completion_date,
                'training_type' => 'Scorm',
            ];
        }
        return $allAssignedTrainings;
    }

    public function getGrade($score)
    {
        if ($score >= 90) {
            $grade = 'A+';
        } elseif ($score >= 80) {
            $grade = 'A';
        } elseif ($score >= 70) {
            $grade = 'B';
        } elseif ($score >= 60) {
            $grade = 'C';
        } else {
            $grade = 'D';
        }
        return $grade;
    }

    public function generateCertificatePdf($trainingModule, $logo, $favIcon)
    {
        $certificateId = $this->getCertificateId($trainingModule->user_email, $trainingModule->training);
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeCertificateId($trainingModule->user_email, $certificateId, $trainingModule->training);
        }

        // Check if custom template exists for this company or not, if it exists then send this certificate otherwise default one
        $customTemplate = CertificateTemplate::where('company_id', $trainingModule->company_id)->where('selected', true)->first();

        $certificateService = new TrainingCertificateService();
        if ($customTemplate) {

            $pdfContent = $certificateService->customTemplate($customTemplate, $trainingModule, $certificateId, $trainingModule->trainingData->name);
            return $pdfContent;
        } else {
            $pdfContent = $certificateService->defaultTemplate($trainingModule, $trainingModule->trainingData->name, $certificateId, $logo, $favIcon);
            return $pdfContent;
        }
    }

    private function getCertificateId($userEmail, $trainingId)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = TrainingAssignedUser::where('training', $trainingId)
            ->where('user_email', $userEmail)
            ->first();

        return $certificate ? $certificate->certificate_id : null;
    }

    private function generateCertificateId()
    {
        // Generate a unique random ID. You can adjust the format as needed.
        return strtoupper(uniqid('CERT-'));
    }

    private function storeCertificateId($userEmail, $certificateId, $trainingId)
    {
        // Find the existing record based on training module and userEmail
        $trainingAssignedUser = TrainingAssignedUser::where('training', $trainingId)
            ->where('user_email', $userEmail)
            ->first();

        // Check if the record was found
        if ($trainingAssignedUser) {
            // Update only the certificate_id (no need to touch campaign_id)
            $trainingAssignedUser->update([
                'certificate_id' => $certificateId,
            ]);
        }
    }

    public function generateScormCertificatePdf($scormTraining, $logo, $favIcon)
    {
        $certificateId = $this->getScormCertificateId($scormTraining->user_email, $scormTraining->scorm);
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeScormCertificateId($scormTraining->user_email, $certificateId, $scormTraining->scorm);
        }

        // Check if custom template exists for this company or not, if it exists then send this certificate otherwise default one
        $customTemplate = CertificateTemplate::where('company_id', $scormTraining->company_id)->where('selected', true)->first();

        $certificateService = new TrainingCertificateService();
        if ($customTemplate) {

            $pdfContent = $certificateService->customTemplate($customTemplate, $scormTraining, $certificateId, $scormTraining->scormTrainingData->name);
            return $pdfContent;
        } else {
            $pdfContent = $certificateService->defaultTemplate($scormTraining, $scormTraining->scormTrainingData->name, $certificateId, $logo, $favIcon);
            return $pdfContent;
        }
    }

    private function getScormCertificateId($userEmail, $scorm)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = ScormAssignedUser::where('scorm', $scorm)
            ->where('user_email', $userEmail)
            ->first();

        return $certificate ? $certificate->certificate_id : null;
    }

    private function storeScormCertificateId($userEmail, $certificateId, $scorm)
    {
        $scormAssignedUser = ScormAssignedUser::where('scorm', $scorm)
            ->where('user_email', $userEmail)
            ->first();

        if ($scormAssignedUser) {
            // Update only the certificate_id (no need to touch campaign_id)
            $scormAssignedUser->update([
                'certificate_id' => $certificateId,
            ]);
        }
    }

    public function saveCertificatePdf($pdfContent, $trainingData)
    {
        $emailFolder = $trainingData->user_email;
        $pdfFileName = 'certificate_' . time() . '.pdf';
        $relativePath =  'certificates/' . $emailFolder . '/' . $pdfFileName;

        // Save using Storage
        Storage::disk('s3')->put($relativePath, $pdfContent);
        $certificate_full_path = Storage::disk('s3')->path($relativePath);
        $trainingData->certificate_path = '/' . $certificate_full_path;
    }

    public function getNormalEmpTrainings($email)
    {
        // Get all non-game trainings
        $allTrainings = TrainingAssignedUser::with('trainingData')
            ->where('user_email', $email)
            ->where('training_type', '!=', 'games')
            ->get();

        // Get completed trainings
        $completedTrainings = TrainingAssignedUser::with('trainingData')
            ->where('user_email', $email)
            ->where('completed', 1)
            ->where('training_type', '!=', 'games')
            ->get();

        // Get in-progress trainings
        $inProgressTrainings = TrainingAssignedUser::with('trainingData')
            ->where('user_email', $email)
            ->where('training_started', 1)
            ->where('completed', 0)
            ->where('training_type', '!=', 'games')
            ->get();

        // Calculate average score for in-progress trainings
        $avgInProgressTrainings = round(
            TrainingAssignedUser::where('user_email', $email)
                ->where('training_started', 1)
                ->where('completed', 0)
                ->where('training_type', '!=', 'games')
                ->avg('personal_best') ?? 0
        );

        $user = Users::where('user_email', $email)->first();
        $employeeReport = new EmployeeReport($email, $user->company_id);

        return [
            'email' => $email,
            'all_trainings' => $allTrainings,
            'completed_trainings' => $completedTrainings,
            'in_progress_trainings' => $inProgressTrainings,
            'total_trainings' => $employeeReport->assignedTrainings(),
            'total_completed_trainings' => $employeeReport->trainingCompleted(),
            'total_in_progress_trainings' => $employeeReport->trainingInProgress(),
            'avg_in_progress_trainings' => $avgInProgressTrainings,
        ];
    }

    public function calculateCompletionRate($email)
    {
        $user = Users::where('user_email', $email)->first();
        $employeeReport = new EmployeeReport($email, $user->company_id);

        $totalTrainings = $employeeReport->assignedTrainings();

        $completedTrainings = $employeeReport->trainingCompleted();

        if ($totalTrainings === 0) {
            return 0;
        }

        $completionRate = ($completedTrainings / $totalTrainings) * 100;
        return round($completionRate, 2);
    }
}
