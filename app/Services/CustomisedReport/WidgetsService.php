<?php

namespace App\Services\CustomisedReport;

use App\Services\CustomisedReport\LineDataService;

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

    public function line($type, $months)
    {
        $lineDataService = new LineDataService($type, $this->companyId);
        return $lineDataService->getData($months);
    }

    public function table($type, $months)
    {
        $tableDataService = new TableDataService($type, $this->companyId);
        return $tableDataService->getData($months);
    }

    public function radial()
    {
        // Logic for radial data
    }

    

    public function bubble()
    {
        // Logic for table data
    }

}
