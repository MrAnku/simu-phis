<?php

namespace App\Services\Reports;

use App\Models\TrainingGame;
use App\Services\Reports\MetricCalculator;

class GamesReportService
{
    public function fetchGamesReport(string $companyId): array
    {
        $trainingGames = TrainingGame::where('company_id', $companyId)->get();
        $games = MetricCalculator::formatGamesReport($trainingGames);

        return ['games' => $games];
    }
}
