<?php

namespace App\Services;

use App\Models\NewIpLogin;

class LoginAlertService
{
    protected $company;
    //constructor
    public function __construct($company)
    {
        $this->company = $company;
    }

    public function checkAndSendAlert(): void
    {
        $ip = getClientIp();
        //check if this company is logged in from a new ip
        if (NewIpLogin::where('email', $this->company->email)->where('ip_address', $ip)->doesntExist()) {

            setCompanyTimezone($this->company->company_id);

            NewIpLogin::create([
                'email' => $this->company->email,
                'ip_address' => $ip,
                'login_time' => now(),
                'timezone' => config('app.timezone'),
                'company_id' => $this->company->company_id,
            ]);
        }
    }
}
