<?php

namespace App\Services\CustomisedReport;

class WidgetsService
{
    protected $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }
    public function card($type)
    {
        $cardDataService = new CardDataService($type, $this->companyId);
        return $cardDataService->getData();
    }

    public function line()
    {
        // Logic for line data
    }

    public function radial()
    {
        // Logic for bar data
    }

    public function table()
    {
        // Logic for area data
    }

    public function bubble()
    {
        // Logic for table data
    }

}
