<?php

declare(strict_types=1);

namespace KateMorley\Grid\Data;

class UsLatestGeneration
{
    public static function fetch(): array
    {
        $source = self::fetchRows();
        $rows = $source['rows'];

        if (!$rows) {
            throw new \RuntimeException('No US fuel mix rows returned.');
        }

        $rowCount = count($rows);
        $rows = self::selectUsWideRows($rows);

        $periodCounts = [];
        $periodRespondents = [];

        foreach ($rows as $row) {
            $rowTime = (string) ($row['time'] ?? '');

            if ($rowTime === '') {
                continue;
            }

            $periodCounts[$rowTime] = ($periodCounts[$rowTime] ?? 0) + 1;

            $respondent = (string) ($row['respondent'] ?? '');
            if ($respondent !== '') {
                $periodRespondents[$rowTime][$respondent] = true;
            }
        }

        $latestTime = self::selectLatestCompleteTime(
            $periodCounts,
            $periodRespondents
        );

        $generation = self::emptyGeneration();

        $latestRows = [];
        $respondents = [];
        $fueltypes = [];
        $fueltypeRows = [];
        $history = [];

        foreach ($rows as $row) {
            self::addRow(
                $row,
                $latestTime,
                $generation,
                $history,
                $latestRows,
                $respondents,
                $fueltypes,
                $fueltypeRows
            );
        }

        foreach ($generation as $key => $value) {
            if ($value < 0) {
                $generation[$key] = 0.0;
            }
        }

        $respondentList = array_keys($respondents);
        sort($respondentList);

        $fueltypeList = array_keys($fueltypes);
        sort($fueltypeList);
        ksort($fueltypeRows);

        return [
            'time' => $latestTime,
            'respondent' => 'US48',
            'source' => [
                'key' => $source['key'],
                'label' => $source['label'],
                'detail' => $source['detail'],
            ],
            'generation' => $generation,
            'history' => self::buildHistory($history, $latestTime),
            'diagnostics' => [
                'source' => $source['key'],
                'source_label' => $source['label'],
                'source_errors' => $source['errors'],
                'row_count' => count($rows),
                'raw_row_count' => $rowCount,
                'latest_row_count' => count($latestRows),
                'respondent_count' => count($respondents),
                'fueltype_count' => count($fueltypes),
                'respondents' => $respondentList,
                'fueltypes' => $fueltypeList,
                'fueltype_rows' => $fueltypeRows,
                'period_count' => count($periodCounts),
                'selected_period_rows' => $periodCounts[$latestTime] ?? 0,
                'selected_period_respondents' => isset($periodRespondents[$latestTime])
                    ? count($periodRespondents[$latestTime])
                    : 0,
            ],
        ];
    }

    private static function fetchRows(): array
    {
        return [
            'rows' => EiaFuelMix::fetchSample(),
            'key' => 'eia',
            'label' => 'EIA',
            'detail' => 'EIA hourly US fuel-mix data',
            'errors' => [],
        ];
    }

    private static function selectUsWideRows(array $rows): array
    {
        $usRows = array_values(array_filter(
            $rows,
            static fn ($row) => (string)($row['respondent'] ?? '') === 'US48'
        ));

        return $usRows ?: $rows;
    }

    private static function selectLatestCompleteTime(
        array $periodCounts,
        array $periodRespondents
    ): string {
        if (!$periodCounts) {
            throw new \RuntimeException(
                'Could not determine latest timestamp from EIA fuel mix rows.'
            );
        }

        $maxRespondents = max(array_map(
            static fn ($respondents) => count($respondents),
            $periodRespondents ?: [[]]
        ));
        $maxRows = max($periodCounts);

        $minimumRespondents = max(1, (int)floor($maxRespondents * 0.9));
        $minimumRows = max(1, (int)floor($maxRows * 0.9));
        $times = array_keys($periodCounts);
        rsort($times);

        foreach ($times as $time) {
            $respondentCount = isset($periodRespondents[$time])
                ? count($periodRespondents[$time])
                : 0;

            if (
                $respondentCount >= $minimumRespondents
                && ($periodCounts[$time] ?? 0) >= $minimumRows
            ) {
                return (string)$time;
            }
        }

        return (string)$times[0];
    }

    private static function addRow(
        array  $row,
        string $latestTime,
        array  &$generation,
        array  &$history,
        array  &$latestRows,
        array  &$respondents,
        array  &$fueltypes,
        array  &$fueltypeRows
    ): void {
        $rowTime = (string) ($row['time'] ?? '');
        $key = (string) ($row['key'] ?? '');
        $value = max(0.0, (float) ($row['value'] ?? 0)) / 1000;

        $respondent = (string) ($row['respondent'] ?? '');
        if ($respondent !== '') {
            $respondents[$respondent] = true;
        }

        $fueltype = (string) ($row['fueltype'] ?? '');
        if ($fueltype !== '') {
            $fueltypes[$fueltype] = true;
            $fueltypeRows[$fueltype] = ($fueltypeRows[$fueltype] ?? 0) + 1;
        }

        if (!array_key_exists($key, $generation)) {
            $key = 'other';
        }

        if (!isset($history[$rowTime])) {
            $history[$rowTime] = self::emptyGeneration();
        }

        $history[$rowTime][$key] += $value;

        if ($rowTime !== $latestTime) {
            return;
        }

        $latestRows[] = $row;
        $generation[$key] += $value;
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

    private static function buildHistory(array $history, string $latestTime): array
    {
        ksort($history);

        $series = [];

        foreach ($history as $time => $generation) {
            if (strcmp((string)$time, $latestTime) > 0) {
                continue;
            }

            $timestamp = strtotime((string)$time);

            if ($timestamp === false) {
                continue;
            }

            $generation = array_map(
                static fn ($value) => max(0.0, (float)$value),
                $generation
            );

            $total = array_sum($generation);

            if ($total <= 0) {
                continue;
            }

            $series[] = [
                'time' => (string)$time,
                'timestamp' => $timestamp,
                'generation' => $generation,
                'total' => $total,
            ];
        }

        return $series;
    }
}
