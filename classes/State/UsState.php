<?php

declare(strict_types=1);

namespace KateMorley\Grid\State;

use KateMorley\Grid\Data\EiaHistoricalGeneration;
use KateMorley\Grid\Data\EiaHistoricalOperations;
use KateMorley\Grid\Data\UsLatestGeneration;
use KateMorley\Grid\Data\UsLatestOperations;
use KateMorley\Grid\Data\UsStateMap;

class UsState
{
    public array $latest;
    public array $map;
    public array $view;
    public Datum $datum;

    public function __construct(array $latest, array $map, array $view, Datum $datum)
    {
        $this->latest = $latest;
        $this->map = $map;
        $this->view = $view;
        $this->datum = $datum;
    }

    public static function build(): self
    {
        $latest = UsLatestGeneration::fetch();

        try {
            $latest['historical_generation'] = EiaHistoricalGeneration::fetch();
        } catch (\Throwable $e) {
            $latest['historical_generation'] = [
                'history' => [],
                'diagnostics' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }

        try {
            $latest['operations'] = UsLatestOperations::fetch();
        } catch (\Throwable $e) {
            $latest['operations'] = [
                'history' => [],
                'by_time' => [],
                'latest' => [],
                'diagnostics' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }

        try {
            $latest['historical_operations'] = EiaHistoricalOperations::fetch();
        } catch (\Throwable $e) {
            $latest['historical_operations'] = [
                'history' => [],
                'diagnostics' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }

        $map = UsStateMap::build($latest);
        $view = UsLatestView::build($latest, $map);

        $datum = new Datum($map, true);

        // Override the UK type grouping with the US one
    

        return new self($latest, $map, $view, $datum);
    }
}
