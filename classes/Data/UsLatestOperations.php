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
        $byTime = [];

        try {
            $interchangeRows = EiaInterchangeData::fetchSample();
        } catch (\Throwable $e) {
            $interchangeError = $e->getMessage();
        }

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
            if (isset($point['canada']) || isset($point['mexico'])) {
                $point['transfers'] =
                    (float)($point['canada'] ?? 0)
                    + (float)($point['mexico'] ?? 0);
            } elseif (isset($point['demand'], $point['generation'])) {
                $point['transfers'] =
                    (float)$point['demand'] - (float)$point['generation'];
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
                || isset($point['canada'])
                || isset($point['mexico'])
        ));
        $latestTransfers = self::latestTransferSnapshot($history);

        return [
            'history' => $history,
            'by_time' => $byTime,
            'latest' => [
                'demand' => self::latestField($history, 'demand'),
                'generation' => self::latestField($history, 'generation'),
                'transfers' => $latestTransfers['transfers']
                    ?? self::latestField($history, 'transfers'),
                'interchange' => self::latestField($history, 'interchange'),
                'canada' => $latestTransfers['canada']
                    ?? self::latestField($history, 'canada'),
                'mexico' => $latestTransfers['mexico']
                    ?? self::latestField($history, 'mexico'),
            ],
            'diagnostics' => [
                'row_count' => count($rows),
                'interchange_row_count' => count($interchangeRows),
                'interchange_error' => $interchangeError,
                'period_count' => count($byTime),
            ],
        ];
    }

    private static function latestTransferSnapshot(array $history): array
    {
        $snapshot = self::latestTransferSnapshotWithFields(
            $history,
            ['canada', 'mexico']
        );

        return $snapshot ?: self::latestTransferSnapshotWithFields(
            $history,
            ['canada', 'mexico'],
            false
        );
    }

    private static function latestTransferSnapshotWithFields(
        array $history,
        array $fields,
        bool  $requireAll = true
    ): array {
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

            $snapshot = [];

            foreach (array_merge($fields, ['transfers']) as $field) {
                if (!isset($point[$field])) {
                    continue;
                }

                $snapshot[$field] = [
                    'time' => (string)$point['time'],
                    'timestamp' => (int)$point['timestamp'],
                    'value' => (float)$point[$field],
                ];
            }

            return $snapshot;
        }

        return [];
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
