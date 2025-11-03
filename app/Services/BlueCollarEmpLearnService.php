<?php

namespace App\Services;

use App\Models\AiCallCampLive;
use App\Models\BlueCollarTrainingUser;
use App\Models\CertificateTemplate;
use App\Models\WaLiveCampaign;
use Illuminate\Support\Facades\Storage;

class BlueCollarEmpLearnService
{

    public function generateCertificatePdf($trainingModule, $logo, $favIcon)
    {
        $certificateId = $this->getCertificateId($trainingModule->user_whatsapp, $trainingModule->training);
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeCertificateId($trainingModule->user_whatsapp, $certificateId, $trainingModule->training);
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

    private function getCertificateId($user_whatsapp, $trainingId)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = BlueCollarTrainingUser::where('training', $trainingId)
            ->where('user_whatsapp', $user_whatsapp)
            ->first();

        return $certificate ? $certificate->certificate_id : null;
    }

    private function generateCertificateId()
    {
        // Generate a unique random ID. You can adjust the format as needed.
        return strtoupper(uniqid('CERT-'));
    }

    private function storeCertificateId($user_whatsapp, $certificateId, $trainingId)
    {
        // Find the existing record based on training module and user_whatsapp
        $trainingAssignedUser = BlueCollarTrainingUser::where('training', $trainingId)
            ->where('user_whatsapp', $user_whatsapp)
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
        $certificateId = $this->getScormCertificateId($scormTraining->user_whatsapp, $scormTraining->scorm);
        if (!$certificateId) {
            $certificateId = $this->generateCertificateId();
            $this->storeScormCertificateId($scormTraining->user_whatsapp, $certificateId, $scormTraining->scorm);
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
    private function getScormCertificateId($user_whatsapp, $scorm)
    {
        // Check the database for an existing certificate ID for this user and training module
        $certificate = BlueCollarTrainingUser::where('scorm', $scorm)
            ->where('user_whatsapp', $user_whatsapp)
            ->first();

        return $certificate ? $certificate->certificate_id : null;
    }
    private function storeScormCertificateId($user_whatsapp, $certificateId, $scorm)
    {
        $scormAssignedUser = BlueCollarTrainingUser::where('scorm', $scorm)
            ->where('user_whatsapp', $user_whatsapp)
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
        $whatsappFolder = $trainingData->user_whatsapp;
        $pdfFileName = 'certificate_' . time() . '.pdf';
        $relativePath =  'certificates/' . $whatsappFolder . '/' . $pdfFileName;

        // Save using Storage
        Storage::disk('s3')->put($relativePath, $pdfContent);
        $certificate_full_path = Storage::disk('s3')->path($relativePath);
        $trainingData->certificate_path = '/' . $certificate_full_path;
    }

    public function calculateRiskScore($user)
    {
        $riskScoreRanges = [
            'poor' => [0, 20],
            'fair' => [21, 40],
            'good' => [41, 60],
            'veryGood' => [61, 80],
            'excellent' => [81, 100],
        ];

        $payloadClicked = $this->payloadClicked($user);
        $compromised = $this->compromised($user);
        $totalSimulations = $this->totalSimulations($user);

        // Risk score calculation
        $riskScore = null;
        $riskLevel = null;

        $totalCompromised = $compromised + $payloadClicked;
        
        $riskScore = $totalSimulations > 0 ? 100 - round(($totalCompromised / $totalSimulations) * 100) : 100;

        // Determine risk level
        foreach ($riskScoreRanges as $label => [$min, $max]) {
            if ($riskScore >= $min && $riskScore <= $max) {
                $riskLevel = $label;
                break;
            }
        }

        return [
            'riskScore' => $riskScore,
            'riskLevel' => $riskLevel,
        ];
    }

    private function payloadClicked($user)
    {
        $whatsapp = WaLiveCampaign::where('user_id', $user->id)
            ->where('company_id', $user->company_id)->where('payload_clicked', 1)->count();

        return $whatsapp;
    }


    private function compromised($user)
    {
        $whatsapp = WaLiveCampaign::where('user_id', $user->id)
            ->where('company_id', $user->company_id)->where('compromised', 1)->count();

        $ai = AiCallCampLive::where('user_id', $user->id)
            ->where('company_id', $user->company_id)->where('compromised', 1)->count();

        return $whatsapp + $ai;
    }

    private function totalSimulations($user)
    {
        $whatsapp = WaLiveCampaign::where('user_id', $user->id)
            ->where('company_id', $user->company_id)->count();

        $ai = AiCallCampLive::where('user_id', $user->id)
            ->where('company_id', $user->company_id)->count();

        return $whatsapp + $ai;
    }
}
