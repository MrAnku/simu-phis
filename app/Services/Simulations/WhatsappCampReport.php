<?php

namespace App\Services\Simulations;

use App\Models\WaLiveCampaign;

class WhatsappCampReport
{
    protected $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    public function messageSent($forMonth = null): int
    {
        $query = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('sent', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function messageViewed($forMonth = null): int
    {
        $query = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('payload_clicked', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function linkClicked($forMonth = null): int
    {
        $query = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('payload_clicked', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function compromised($forMonth = null): int
    {
        $query = WaLiveCampaign::where('company_id', $this->companyId)
            ->where('compromised', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function totalAttempts($forMonth = null): int
    {
        $query = WaLiveCampaign::where('company_id', $this->companyId);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

}