<?php

namespace App\Services;

use App\Models\Policy;
use App\Models\AssignedPolicy;
use App\Mail\PolicyCampaignEmail;
use Illuminate\Support\Facades\Mail;

class PolicyAssignedService
{
    protected $companyId;
    protected $campaignId;
    protected $userName;
    protected $userEmail;
    protected $policyNames = [];
    //constructor
    public function __construct($campaignId, $userName, $userEmail, $companyId)
    {
        $this->campaignId = $campaignId;
        $this->userName = $userName;
        $this->userEmail = $userEmail;
        $this->companyId = $companyId;
        $this->companyId = $companyId;
    }
    private function checkAlreadyAssigned($policyId): bool
    {
        return AssignedPolicy::where('user_email', $this->userEmail)
            ->where('policy', $policyId)
            ->exists();
    }

    public function assignPolicies(string $policyIds)
    {
        $policyIdArray = json_decode($policyIds, true);
        foreach ($policyIdArray as $policyId) {
            $policy = Policy::where('id', $policyId)->first();
            if (!$policy) {
                continue;
            }
            if (!$this->checkAlreadyAssigned($policyId)) {
                AssignedPolicy::create([
                    'campaign_id' => $this->campaignId,
                    'user_name' => $this->userName,
                    'user_email' => $this->userEmail,
                    'policy' => $policyId,
                    'company_id' => $this->companyId,
                ]);

                // Audit log
                audit_log(
                    $this->companyId,
                    $this->userEmail,
                    null,
                    'POLICY_ASSIGNED',
                    "'{$policy->policy_name}' has been assigned to {$this->userEmail}",
                    'normal'
                );
            }
            $this->policyNames[] = $policy->policy_name;
        }
        if (!empty($this->policyNames)) {
            $this->sendNotificationEmail($this->policyNames);
        }
    }
    public function assignPolicy(int $policyId) {}

    private function sendNotificationEmail($policyName)
    {
        $mailData = [
            'user_name' => $this->userName,
            'policy_names' => $this->policyNames,
            'assigned_at' => now()->toDateTimeString(),
            'company_id' => $this->companyId
        ];

        try {
            Mail::to($this->userEmail)->send(new PolicyCampaignEmail($mailData));
            echo "Policy has been assigned and email sent to {$this->userEmail}\n";
        } catch (\Exception $e) {
            echo 'Failed to send email: ' . $e->getMessage() . "\n";
        }
    }
}
