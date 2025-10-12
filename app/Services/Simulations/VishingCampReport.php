<?php

namespace App\Services\Simulations;

use App\Models\AiCallCampLive;

class VishingCampReport
{
    protected $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    public function callsSent($forMonth = null)
    {
        $query = AiCallCampLive::where('company_id', $this->companyId)
            ->where('status', '!=', 'pending');


        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function callsReceived($forMonth = null)
    {
        $query = AiCallCampLive::where('company_id', $this->companyId)
            ->whereIn('status', ['waiting', 'in-progress', 'completed']);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function compromised($forMonth = null)
    {
        $query = AiCallCampLive::where('company_id', $this->companyId)
            ->where('compromised', 1);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function busyCalls($forMonth = null)
    {
        $query = AiCallCampLive::where('company_id', $this->companyId)
            ->where('status', 'busy');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function completedCalls($forMonth = null)
    {
        $query = AiCallCampLive::where('company_id', $this->companyId)
            ->where('status', 'completed');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function callInProgress($forMonth = null)
    {
        $query = AiCallCampLive::where('company_id', $this->companyId)
            ->where('status', 'in-progress');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function callsFailed($forMonth = null)
    {
        $query = AiCallCampLive::where('company_id', $this->companyId)
            ->where('status', 'failed');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function canceled($forMonth = null)
    {
        $query = AiCallCampLive::where('company_id', $this->companyId)
            ->where('status', 'canceled');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function noAnswer($forMonth = null)
    {
        $query = AiCallCampLive::where('company_id', $this->companyId)
            ->where('status', 'no-answer');

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }

    public function totalAttempts($forMonth = null)
    {
        $query = AiCallCampLive::where('company_id', $this->companyId);

        if ($forMonth) {
            $query->whereMonth('created_at', $forMonth);
        }

        return $query->count();
    }
}
