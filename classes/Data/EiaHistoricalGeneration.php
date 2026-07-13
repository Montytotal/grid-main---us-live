<?php

declare(strict_types=1);

namespace KateMorley\Grid\Data;

/** Builds long-range US generation summaries from monthly EIA-923 data. */
class EiaHistoricalGeneration
{
    private const PAGE_LENGTH = 5000;

    /**
     * These EIA fuel groups are mutually exclusive and together reconcile to
     * the all-fuels total. Pumped storage is included in hydro as its reported
     * (sometimes negative) net generation.
     */
    private const FUEL_MAP = [
        'COW' => 'coal',
        'NGO' => 'gas',
        'NUC' => 'nuclear',
        'SUN' => 'solar',
        'WND' => 'wind',
        'HYC' => 'hydro',
        'HPS' => 'hydro',
        'PET' => 'oil',
        'BIO' => 'biomass',
        'GEO' => 'other',
        'OTH' => 'other',
    ];

    public static function fetch(): array
    {
        $apiKey = getenv('EIA_API_KEY');

        if (!is_string($apiKey) || $apiKey === '') {
            throw new DataException('Missing EIA API key');
        }

        $rows = self::fetchRows($apiKey);
        $months = [];

        foreach ($rows as $row) {
            $period = (string)($row['period'] ?? '');
            $fuelType = (string)($row['fueltypeid'] ?? '');

            if (
                !preg_match('/^\d{4}-\d{2}$/', $period)
                || !isset(self::FUEL_MAP[$fuelType])
                || !is_numeric($row['generation'] ?? null)
            ) {
                continue;
            }

            if (!isset($months[$period])) {
                $months[$period] = self::emptyGeneration();
            }

            $key = self::FUEL_MAP[$fuelType];
            $months[$period][$key] += (float)$row['generation'];
        }

        ksort($months);
        $history = [];

        foreach ($months as $period => $energy) {
            $start = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $period . '-01',
                new \DateTimeZone('UTC')
            );

            if (!$start) {
                continue;
            }

            $end = $start->modify('first day of next month');
            $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;

            if ($hours <= 0) {
                continue;
            }

            // EIA reports generation in thousand MWh. Dividing by hours gives
            // average GW for the month.
            $generation = array_map(
                static fn ($value) => max(0.0, (float)$value / $hours),
                $energy
            );
            $total = array_sum($generation);

            if ($total <= 0) {
                continue;
            }

            $history[] = [
                'time' => $period,
                'timestamp' => $start->getTimestamp(),
                'generation' => $generation,
                'total' => $total,
                'weight' => $hours,
            ];
        }

        if (!$history) {
            throw new DataException('No historical US generation rows returned');
        }

        return [
            'history' => $history,
            'start' => (string)$history[0]['time'],
            'end' => (string)$history[count($history) - 1]['time'],
            'source' => [
                'key' => 'eia-923',
                'label' => 'EIA-923',
                'detail' => 'Monthly US generation by energy source',
            ],
            'diagnostics' => [
                'row_count' => count($rows),
                'month_count' => count($history),
            ],
        ];
    }

    private static function fetchRows(string $apiKey): array
    {
        $fuelFacets = '';

        foreach (array_keys(self::FUEL_MAP) as $fuelType) {
            $fuelFacets .= '&facets[fueltypeid][]=' . urlencode($fuelType);
        }

        $url = sprintf(
            'https://api.eia.gov/v2/electricity/electric-power-operational-data/data/?api_key=%s&frequency=monthly&data[0]=generation&facets[location][]=US&facets[sectorid][]=99%s&sort[0][column]=period&sort[0][direction]=asc&offset=0&length=%d',
            urlencode($apiKey),
            $fuelFacets,
            self::PAGE_LENGTH
        );
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'header' => implode("\r\n", [
                    'User-Agent: Mozilla/5.0',
                    'Accept: application/json',
                ]),
            ],
        ]);
        $rawData = false;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            if ($attempt > 0) {
                sleep($attempt);
            }

            $rawData = @file_get_contents($url, false, $context);

            if ($rawData !== false) {
                break;
            }
        }

        if ($rawData === false) {
            throw new DataException('Failed to read historical EIA generation data');
        }

        $jsonData = json_decode($rawData, true);
        $rows = $jsonData['response']['data'] ?? null;

        if (!is_array($rows)) {
            throw new DataException('Missing historical EIA generation data');
        }

        $total = (int)($jsonData['response']['total'] ?? count($rows));

        if ($total > count($rows)) {
            throw new DataException('Historical EIA generation data was truncated');
        }

        return $rows;
    }

    private static function emptyGeneration(): array
    {
        return [
            'coal' => 0.0,
            'gas' => 0.0,
            'nuclear' => 0.0,
            'solar' => 0.0,
            'wind' => 0.0,
            'hydro' => 0.0,
            'oil' => 0.0,
            'biomass' => 0.0,
            'other' => 0.0,
        ];
    }
}
