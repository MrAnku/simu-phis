<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use Illuminate\Support\Facades\Storage;

class NormalEmpLearnService
{
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
}
