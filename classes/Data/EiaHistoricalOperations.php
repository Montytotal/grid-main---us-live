<?php

declare(strict_types=1);

namespace KateMorley\Grid\Data;

/** Builds long-range US operating history from daily EIA-930 data. */
class EiaHistoricalOperations
{
    private const PAGE_LENGTH = 5000;
    private const MAX_ROWS = 15000;
    private const RESPONDENT = 'US48';
    private const TIMEZONE = 'Arizona';
    private const TYPES = ['D', 'NG', 'TI'];

    public static function fetch(): array
    {
        $apiKey = getenv('EIA_API_KEY');

        if (!is_string($apiKey) || $apiKey === '') {
            throw new DataException('Missing EIA API key');
        }

        $rows = self::fetchRows($apiKey);
        $byDay = [];

        foreach ($rows as $row) {
            $period = (string)($row['period'] ?? '');
            $type = (string)($row['type'] ?? '');
            $value = $row['value'] ?? null;

            if (
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $period)
                || !in_array($type, self::TYPES, true)
                || !is_numeric($value)
                || (string)($row['value-units'] ?? 'megawatthours') !== 'megawatthours'
            ) {
                continue;
            }

            $date = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $period,
                new \DateTimeZone('UTC')
            );

            if (!$date) {
                continue;
            }

            if (!isset($byDay[$period])) {
                $byDay[$period] = [
                    'time' => $period,
                    'timestamp' => $date->getTimestamp(),
                ];
            }

            // The daily route reports MWh. Arizona is a fixed-offset reporting
            // day, so every bucket contains 24 hours. Convert to average GW.
            $averageGw = (float)$value / 24 / 1000;

            switch ($type) {
                case 'D':
                    $byDay[$period]['demand'] = max(0.0, $averageGw);
                    break;

                case 'NG':
                    $byDay[$period]['generation'] = max(0.0, $averageGw);
                    break;

                case 'TI':
                    $byDay[$period]['interchange'] = $averageGw;
                    $byDay[$period]['net_imports'] = -$averageGw;
                    break;
            }
        }

        ksort($byDay);
        $history = array_values(array_filter(
            $byDay,
            static fn ($point) => isset(
                $point['demand'],
                $point['generation'],
                $point['net_imports']
            )
        ));

        if (!$history) {
            throw new DataException('No historical EIA operating rows returned');
        }

        return [
            'history' => $history,
            'start' => (string)$history[0]['time'],
            'end' => (string)$history[count($history) - 1]['time'],
            'source' => [
                'key' => 'eia-930-daily',
                'label' => 'EIA-930',
                'detail' => 'Daily US48 demand, net generation and total interchange',
            ],
            'diagnostics' => [
                'row_count' => count($rows),
                'day_count' => count($history),
                'reported_day_count' => count($byDay),
                'complete_day_count' => count($history),
                'timezone' => self::TIMEZONE,
            ],
        ];
    }

    private static function fetchRows(string $apiKey): array
    {
        $typeFacets = '';

        foreach (self::TYPES as $type) {
            $typeFacets .= '&facets[type][]=' . urlencode($type);
        }

        $baseUrl = sprintf(
            'https://api.eia.gov/v2/electricity/rto/daily-region-data/data/?api_key=%s&frequency=daily&data[0]=value&facets[respondent][]=%s&facets[timezone][]=%s%s&start=2019-01-01&end=%s&sort[0][column]=period&sort[0][direction]=asc',
            urlencode($apiKey),
            urlencode(self::RESPONDENT),
            urlencode(self::TIMEZONE),
            $typeFacets,
            gmdate('Y-m-d', time() - 86400)
        );
        $rows = [];
        $total = null;

        for ($offset = 0; ; $offset += self::PAGE_LENGTH) {
            if ($offset >= self::MAX_ROWS) {
                throw new DataException('Historical EIA operating data exceeded its safety limit');
            }

            [$pageRows, $pageTotal] = self::fetchPage($baseUrl, $offset);
            $total ??= $pageTotal;

            if ($pageTotal !== $total) {
                throw new DataException('Historical EIA operating total changed during pagination');
            }

            $rows = array_merge($rows, $pageRows);

            if (count($pageRows) < self::PAGE_LENGTH || count($rows) >= $total) {
                break;
            }
        }

        if (count($rows) < (int)$total) {
            throw new DataException('Historical EIA operating data was truncated');
        }

        return $rows;
    }

    private static function fetchPage(string $baseUrl, int $offset): array
    {
        $url = $baseUrl . sprintf(
            '&offset=%d&length=%d',
            $offset,
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
            throw new DataException('Failed to read historical EIA operating data');
        }

        $jsonData = json_decode($rawData, true);
        $pageRows = $jsonData['response']['data'] ?? null;

        if (!is_array($pageRows)) {
            throw new DataException('Missing historical EIA operating data');
        }

        $total = (int)($jsonData['response']['total'] ?? count($pageRows));

        return [$pageRows, $total];
    }
}
