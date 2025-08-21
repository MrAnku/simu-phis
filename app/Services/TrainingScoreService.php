<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use App\Models\ScormAssignedUser;
use App\Models\TrainingAssignedUser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class TrainingScoreService
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

        if ($customTemplate) {

            $pdfContent = $this->customTemplate($customTemplate, $trainingModule, $certificateId, $logo, $favIcon);
            return $pdfContent;
        } else {
            $pdfContent = $this->defaultTemplate($trainingModule, $trainingModule->trainingData->name, $certificateId, $logo, $favIcon);
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

        if ($customTemplate) {

            $pdfContent = $this->customTemplate($customTemplate, $scormTraining, $certificateId, $logo, $favIcon);
            return $pdfContent;
        } else {
            $pdfContent = $this->defaultTemplate($scormTraining, $scormTraining->scormTrainingData->name, $certificateId, $logo, $favIcon);
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

    private function defaultTemplate($training, $trainingName, $certificateId, $logo, $favIcon)
    {
        $pdf = new Fpdi();
        $pdf->AddPage('L', 'A4');
        $pdf->setSourceFile(resource_path('templates/design.pdf'));
        $template = $pdf->importPage(1);
        $pdf->useTemplate($template);

        $name = $training->user_name;

        // Truncate name if too long
        if (strlen($name) > 15) {
            $name = mb_substr($name, 0, 12) . '...';
        }

        // Add user name
        $pdf->SetFont('Helvetica', '', 50);
        $pdf->SetTextColor(47, 40, 103);
        $pdf->SetXY(100, 115);
        $pdf->Cell(0, 10, ucwords($name), 0, 1, 'L');

        // Add training module
        $pdf->SetFont('Helvetica', '', 16);
        $pdf->SetTextColor(169, 169, 169);
        $pdf->SetXY(100, 135);
        $pdf->Cell(210, 10, "For completing {$trainingName}", 0, 1, 'L');

        // Add date and certificate ID
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(240, 165);
        $pdf->Cell(50, 10, "Completion date: {$training->completion_date}", 0, 0, 'R');

        $pdf->SetXY(240, 10);
        $pdf->Cell(50, 10, "Certificate ID: $certificateId", 0, 0, 'R');

        if ($logo || file_exists($logo)) {
            // 1. Top-left corner (e.g., branding)
            $pdf->Image($logo, 100, 12, 50); // X=15, Y=12, Width=40mm           
        }

        // 2. Bottom-center badge
        $pdf->Image($favIcon, 110, 163, 15, 15);

        return $pdf->Output('S');
    }

    private function customTemplate($customTemplate, $trainingModule, $certificateId, $logo)
    {
        $templateContent = Storage::disk('s3')->get($customTemplate->filepath);
        $certificateHtml = str_replace(
            ['{{logo_url}}', '{{certificate_id}}', '{{learner_name}}', '{{training_name}}', '{{completion_date}}'],
            [
                $logo,
                $certificateId,
                $trainingModule->user_name,
                $trainingModule->trainingData->name,
                $trainingModule->completion_date
                // $favIcon
            ],
            $templateContent
        );

        $pdf = Pdf::loadHTML($certificateHtml);
        $pdf->setOptions(['isRemoteEnabled' => true]);
        $pdfContent = $pdf->output();
        return $pdfContent;
    }
}
