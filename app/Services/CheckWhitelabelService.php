<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyBranding;
use InvalidArgumentException;
use App\Models\WhiteLabelledSmtp;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Config;
use App\Models\WhiteLabelledWhatsappConfig;

class CheckWhitelabelService extends BrandingService
{
    public $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
        parent::__construct($companyId);
    }
    /**
     * Check if a company is whitelabeled.
     *
     * @param string $companyId Identifier or Company instance
     * @return bool
     * 
     */
    public function isCompanyWhitelabeled(): bool
    {
        $exist = WhiteLabelledCompany::where('company_id', $this->companyId)
            ->where('approved_by_partner', 1)
            ->where('service_status', 1)
            ->exists();
        if (!$exist) {
            return false;
        }
        return true;
    }

    public function getWhiteLabelData(): object
    {
        $domainDetails = WhiteLabelledCompany::where('company_id', $this->companyId)
            ->where('approved_by_partner', 1)
            ->where('service_status', 1)
            ->first();
        $brandingDetails = CompanyBranding::where('company_id', $this->companyId)
            ->first();
        return $domainDetails->merge($brandingDetails);
    }

    public function updateSmtpConfig(): void
    {
        $smtpData = WhiteLabelledSmtp::where('company_id', $this->companyId)
            ->first();
        if ($smtpData) {
            config([
                'mail.mailers.smtp.host' => $smtpData->smtp_host,
                'mail.mailers.smtp.port' => $smtpData->smtp_port,
                'mail.mailers.smtp.username' => $smtpData->smtp_username,
                'mail.mailers.smtp.password' => $smtpData->smtp_password,
                'mail.mailers.smtp.encryption' => $smtpData->smtp_encryption,
                'mail.from.address' => $smtpData->from_address,
                'mail.from.name' => $smtpData->from_name,
            ]);
        }
    }

    public function clearSmtpConfig(): void
    {
         //reset the smtp config to default if not whitelabeled
            Config::set([
                'mail.mailers.smtp.host' => env('MAIL_HOST'),
                'mail.mailers.smtp.port' => env('MAIL_PORT'),
                'mail.mailers.smtp.username' => env('MAIL_USERNAME'),
                'mail.mailers.smtp.password' => env('MAIL_PASSWORD'),
                'mail.mailers.smtp.encryption' => env('MAIL_ENCRYPTION'),
                'mail.from.address' => env('MAIL_FROM_ADDRESS'),
                'mail.from.name' => env('MAIL_FROM_NAME'),
            ]);
            
            // Refresh the mail manager to apply new configuration
            app('mail.manager')->purge();
    }

    public function geṭWhatsappConfig(): object
    {
        return WhiteLabelledWhatsappConfig::where('company_id', $this->companyId)
            ->first();
    }

    public function platformDomain(): string
    {
        $domain = WhiteLabelledCompany::where('company_id', $this->companyId)
            ->where('approved_by_partner', 1)
            ->where('service_status', 1)
            ->value('domain');
        if($domain){
            return "https://" .$domain;
        }
        return env('NEXT_APP_URL');
    }

    public function learningPortalDomain(): string
    {
        $learnDomain = WhiteLabelledCompany::where('company_id', $this->companyId)
            ->where('approved_by_partner', 1)
            ->where('service_status', 1)
            ->value('learn_domain');
        if($learnDomain){
            return "https://" .$learnDomain;
        }
        return env('SIMUPHISH_LEARNING_URL');
    }
}
