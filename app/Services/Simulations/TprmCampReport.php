<?php

namespace App\Services\Simulations;

use App\Models\TprmCampaignLive;

class TprmCampReport
{
    protected $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    public function emailSent($forMonth = null): int
    {
        $query = TprmCampaignLive::where('company_id', $this->companyId)
            ->where('sent', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function emailViewed($forMonth = null): int
    {
        $query = TprmCampaignLive::where('company_id', $this->companyId)
            ->where('mail_open', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function payloadClicked($forMonth = null): int
    {
        $query = TprmCampaignLive::where('company_id', $this->companyId)
            ->where('payload_clicked', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function emailReported($forMonth = null): int
    {
        $query = TprmCampaignLive::where('company_id', $this->companyId)
            ->where('email_reported', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function compromised($forMonth = null): int
    {
        $query = TprmCampaignLive::where('company_id', $this->companyId)
            ->where('emp_compromised', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

}