<?php

namespace KateMorley\Grid\State;

class UsLatestView
{
    public static function build(array $latest, array $map = []): array
    {
        $generation = array_map(
            static fn ($value) => max(0.0, (float)$value),
            $latest['generation'] ?? []
        );

        $sources = [
            'Gas'     => (float)($generation['gas'] ?? 0),
            'Hydroelectric' => (float)($generation['hydro'] ?? 0),
            'Wind'    => (float)($generation['wind'] ?? 0),
            'Solar'   => (float)($generation['solar'] ?? 0),
            'Nuclear' => (float)($generation['nuclear'] ?? 0),
            'Coal'    => (float)($generation['coal'] ?? 0),
            'Biomass' => (float)($generation['biomass'] ?? 0),
            'Oil'     => (float)($generation['oil'] ?? 0),
            'Other'   => (float)($generation['other'] ?? 0),
        ];

        $sources = array_filter(
            $sources,
            static fn ($value) => $value > 0
        );

        arsort($sources, SORT_NUMERIC);

        $types = [
            'Fossils'    => (float)($generation['coal'] ?? 0)
                          + (float)($generation['gas'] ?? 0)
                          + (float)($generation['oil'] ?? 0),

            'Renewables' => (float)($generation['solar'] ?? 0)
                          + (float)($generation['wind'] ?? 0)
                          + (float)($generation['hydro'] ?? 0),

            'Others'     => (float)($generation['nuclear'] ?? 0)
                          + (float)($generation['biomass'] ?? 0)
                          + (float)($generation['other'] ?? 0),
        ];

        $types = array_filter(
            $types,
            static fn ($value) => $value > 0
        );

        $totalGeneration = array_sum($sources);
        $equation = self::latestEquation($latest);

        $sourceLabel = (string)($latest['source']['label'] ?? 'EIA');

        return [
            'title'      => 'US energy mix',
            'subtitle'   => 'Latest ' . $sourceLabel . ' fuel-mix sample',
            'respondent' => $latest['respondent'] ?? 'Unknown',
            'time_raw'   => $latest['time'] ?? '',
            'time_label' => self::formatTime($latest['time'] ?? ''),

            'sources' => self::rows($sources, $totalGeneration),
            'types'   => self::rows($types, $totalGeneration),
            'equation' => $equation,

            'summary' => [
                'generation'   => $totalGeneration,
                'source_count' => count($sources),
                'largest_source' => self::largestSource($sources),
            ],
        ];
    }

    private static function latestEquation(array $latest): array
    {
        $operations = $latest['operations']['latest']['balance'] ?? [];

        if (!is_array($operations) || !isset(
            $operations['demand'],
            $operations['generation'],
            $operations['net_imports']
        )) {
            return [];
        }

        return [
            'time' => (string)($operations['time'] ?? ''),
            'timestamp' => (int)($operations['timestamp'] ?? 0),
            'demand' => max(0.0, (float)$operations['demand']),
            'generation' => max(0.0, (float)$operations['generation']),
            'net_imports' => (float)$operations['net_imports'],
        ];
    }

    private static function largestSource(array $sources): array
    {
        if (!$sources) {
            return [
                'label' => 'None',
                'value' => 0.0,
            ];
        }

        $label = array_key_first($sources);

        return [
            'label' => $label,
            'value' => (float)$sources[$label],
        ];
    }

    private static function rows(array $values, float $total): array
    {
        $rows = [];

        foreach ($values as $label => $value) {
            $rows[] = [
                'label' => $label,
                'value' => (float)$value,
                'share' => $total > 0 ? ((float)$value / $total) : 0.0,
            ];
        }

        return $rows;
    }

    private static function formatTime(string $time): string
    {
        if ($time === '') {
            return 'Unknown time';
        }

        $dt = date_create($time);

        if (!$dt) {
            return $time;
        }

        return gmdate('Y-m-d H:i \U\T\C', (int)$dt->format('U'));
    }
}
