<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class TrainingCertificateService
{

    public function defaultTemplate($training, $trainingName, $certificateId, $logo, $favIcon)
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

    public function customTemplate($customTemplate, $trainingModule, $certificateId, $trainingName)
    {
        $templateContent = Storage::disk('s3')->get($customTemplate->filepath);
        $certificateHtml = str_replace(
            ['{{certificate_id}}', '{{learner_name}}', '{{training_name}}', '{{completion_date}}'],
            [
                $certificateId,
                $trainingModule->user_name,
                $trainingName,
                $trainingModule->completion_date
            ],
            $templateContent
        );

        $pdf = Pdf::loadHTML($certificateHtml);
        $pdf->setOptions(['isRemoteEnabled' => true]);
        $pdf->setPaper('a4', 'landscape'); // Set orientation to landscape
        $pdfContent = $pdf->output();
        return $pdfContent;
    }
}
