<?php

declare(strict_types=1);

namespace KateMorley\Grid\Data;

class UsLatestOperations
{
    public static function fetch(): array
    {
        $rows = EiaRegionData::fetchSample();
        $interchangeRows = [];
        $interchangeError = null;

        try {
            $interchangeRows = EiaInterchangeData::fetchSample();
        } catch (\Throwable $e) {
            $interchangeError = $e->getMessage();
        }

        return self::buildFromRows(
            $rows,
            $interchangeRows,
            $interchangeError
        );
    }

    public static function buildFromRows(
        array   $rows,
        array   $interchangeRows = [],
        ?string $interchangeError = null
    ): array {
        $byTime = [];

        foreach ($rows as $row) {
            $time = (string)($row['time'] ?? '');
            $timestamp = strtotime($time);

            if ($time === '' || $timestamp === false) {
                continue;
            }

            if (!isset($byTime[$time])) {
                $byTime[$time] = [
                    'time' => $time,
                    'timestamp' => $timestamp,
                ];
            }

            $value = (float)($row['value'] ?? 0) / 1000;

            switch ((string)($row['type'] ?? '')) {
                case 'D':
                    $byTime[$time]['demand'] = max(0.0, $value);
                    break;

                case 'NG':
                    $byTime[$time]['generation'] = max(0.0, $value);
                    break;

                case 'TI':
                    $byTime[$time]['interchange'] = $value;
                    break;
            }
        }

        foreach ($interchangeRows as $row) {
            $time = (string)($row['time'] ?? '');
            $timestamp = strtotime($time);
            $neighbour = (string)($row['neighbour'] ?? '');

            if (
                $time === ''
                || $timestamp === false
                || !in_array($neighbour, ['canada', 'mexico'], true)
            ) {
                continue;
            }

            if (!isset($byTime[$time])) {
                $byTime[$time] = [
                    'time' => $time,
                    'timestamp' => $timestamp,
                ];
            }

            $byTime[$time][$neighbour] =
                ($byTime[$time][$neighbour] ?? 0)
                + ((float)($row['value'] ?? 0) / 1000);
        }

        ksort($byTime);

        foreach ($byTime as &$point) {
            if (isset($point['interchange'])) {
                // EIA reports positive total interchange as an outflow from
                // US48. The public UI uses the opposite convention so that
                // positive values mean net imports.
                $point['net_imports'] = UsNetFlow::importsPositive(
                    (float)$point['interchange']
                );
            }
        }
        unset($point);

        $history = array_values(array_filter(
            $byTime,
            static fn ($point) => isset(
                $point['demand'],
                $point['generation']
            )
                || isset($point['demand'])
                || isset($point['generation'])
                || isset($point['net_imports'])
                || isset($point['canada'])
                || isset($point['mexico'])
        ));
        $newestBalance = self::latestCompletePoint(
            $history,
            ['demand', 'generation', 'interchange', 'net_imports']
        );
        $latestAlignedBalance = self::latestCompletePointWithCountry(
            $history,
            ['demand', 'generation', 'interchange', 'net_imports'],
            true
        );

        if (!$latestAlignedBalance) {
            $latestAlignedBalance = self::latestCompletePointWithCountry(
                $history,
                ['demand', 'generation', 'interchange', 'net_imports'],
                false
            );
        }

        // When direct country data is available for a complete national
        // balance hour, use that one timestamp everywhere in the current
        // view. Otherwise retain the newest complete US48 balance.
        $latestBalance = $latestAlignedBalance ?: $newestBalance;
        $latestCountry = $latestAlignedBalance
            ? self::countrySnapshotFromPoint($latestAlignedBalance, true)
            : self::latestCountrySnapshot($history);

        return [
            'history' => $history,
            'by_time' => $byTime,
            'latest' => [
                'balance' => $latestBalance,
                'newest_balance' => $newestBalance,
                'demand' => self::latestField($history, 'demand'),
                'generation' => self::latestField($history, 'generation'),
                'net_imports' => self::latestField($history, 'net_imports'),
                'interchange' => self::latestField($history, 'interchange'),
                'country' => $latestCountry,
            ],
            'diagnostics' => [
                'row_count' => count($rows),
                'interchange_row_count' => count($interchangeRows),
                'interchange_error' => $interchangeError,
                'period_count' => count($byTime),
            ],
        ];
    }

    private static function latestCompletePoint(
        array $history,
        array $requiredFields
    ): array {
        for ($index = count($history) - 1; $index >= 0; $index --) {
            $point = $history[$index];

            foreach ($requiredFields as $field) {
                if (!isset($point[$field])) {
                    continue 2;
                }
            }

            return $point;
        }

        return [];
    }

    private static function latestCompletePointWithCountry(
        array $history,
        array $requiredFields,
        bool  $requireBothCountries
    ): array {
        for ($index = count($history) - 1; $index >= 0; $index --) {
            $point = $history[$index];

            foreach ($requiredFields as $field) {
                if (!isset($point[$field])) {
                    continue 2;
                }
            }

            $hasCanada = isset($point['canada']);
            $hasMexico = isset($point['mexico']);

            if ($requireBothCountries ? !($hasCanada && $hasMexico) : !($hasCanada || $hasMexico)) {
                continue;
            }

            return $point;
        }

        return [];
    }

    private static function latestCountrySnapshot(array $history): array
    {
        $snapshot = self::latestCountrySnapshotWithFields($history, true);

        return $snapshot ?: self::latestCountrySnapshotWithFields($history, false);
    }

    private static function latestCountrySnapshotWithFields(
        array $history,
        bool  $requireAll = true
    ): array {
        $fields = ['canada', 'mexico'];

        for ($index = count($history) - 1; $index >= 0; $index --) {
            $point = $history[$index];
            $hasAny = false;
            $hasAll = true;

            foreach ($fields as $field) {
                if (isset($point[$field])) {
                    $hasAny = true;
                } else {
                    $hasAll = false;
                }
            }

            if (($requireAll && !$hasAll) || (!$requireAll && !$hasAny)) {
                continue;
            }

            return self::countrySnapshotFromPoint($point, false);
        }

        return [];
    }

    private static function countrySnapshotFromPoint(
        array $point,
        bool  $alignedWithBalance
    ): array {
        $values = [];

        foreach (['canada', 'mexico'] as $field) {
            if (isset($point[$field])) {
                $values[$field] = (float)$point[$field];
            }
        }

        $reportedSubtotal = array_sum($values);
        $snapshot = [
            'time' => (string)($point['time'] ?? ''),
            'timestamp' => (int)($point['timestamp'] ?? 0),
            'values' => $values,
            'both_countries' => count($values) === 2,
            'reported_country_subtotal' => $reportedSubtotal,
            'aligned_with_balance' => $alignedWithBalance,
        ];

        if ($alignedWithBalance && isset($point['net_imports'])) {
            $national = (float)$point['net_imports'];
            $snapshot['national_net_imports'] = $national;
            $snapshot['reconciliation_difference'] =
                $national - $reportedSubtotal;
        }

        return $snapshot;
    }

    private static function latestField(array $history, string $field): ?array
    {
        for ($index = count($history) - 1; $index >= 0; $index --) {
            if (!isset($history[$index][$field])) {
                continue;
            }

            return [
                'time' => (string)$history[$index]['time'],
                'timestamp' => (int)$history[$index]['timestamp'],
                'value' => (float)$history[$index][$field],
            ];
        }

        return null;
    }
}
