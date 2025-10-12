<?php

namespace App\Services\Simulations;

use App\Models\QuishingLiveCamp;

class QuishingCampReport
{
    protected $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    public function emailSent($forMonth = null): int
    {
        $query = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('sent', '1');
            

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function emailViewed($forMonth = null): int
    {
        $query = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('mail_open', '1');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function qrScanned($forMonth = null): int
    {
        $query = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('qr_scanned', '1');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function emailReported($forMonth = null): int
    {
        $query = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('email_reported', '1');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function compromised($forMonth = null): int
    {
        $query = QuishingLiveCamp::where('company_id', $this->companyId)
            ->where('compromised', '1');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function totalAttempts($forMonth = null): int
    {
        $query = QuishingLiveCamp::where('company_id', $this->companyId);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

}