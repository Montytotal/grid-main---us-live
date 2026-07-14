<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\UsState;

class UsTabs {
  private const PERIODS = [
    ['tab-panel-day', 'tab-day', 'Past day', 86400, 6, 'g:ia', 0, 'live', true],
    ['tab-panel-week', 'tab-week', 'Past week', 604800, 24, 'D', 21600, 'live', true],
    ['tab-panel-year', 'tab-year', 'Past year', 31536000, 168, 'j M', 86400, 'year', false],
    ['tab-panel-all', 'tab-all', 'All time', null, 168, 'Y', 86400, 'all', false],
  ];

  private const SOURCE_META = [
    'gas' => ['class' => 'gas', 'label' => 'Gas'],
    'nuclear' => ['class' => 'nuclear', 'label' => 'Nuclear'],
    'wind' => ['class' => 'wind', 'label' => 'Wind'],
    'hydro' => ['class' => 'hydro', 'label' => 'Hydroelectric'],
    'solar' => ['class' => 'solar', 'label' => 'Solar'],
    'coal' => ['class' => 'coal', 'label' => 'Coal'],
    'biomass' => ['class' => 'biomass', 'label' => 'Biomass'],
    'oil' => ['class' => 'oil', 'label' => 'Oil'],
    'other' => ['class' => 'others', 'label' => 'Other'],
  ];

  private const SOURCE_ORDER = [
    'coal',
    'gas',
    'oil',
    'solar',
    'wind',
    'hydro',
    'nuclear',
    'biomass',
    'other'
  ];

  public static function output(UsState $state): void {
    $history = self::getHistory($state);
    $historicalHistory = self::getHistoricalHistory($state);
?>
      <section id="history" aria-labelledby="history-heading">
        <h2 id="history-heading" class="visually-hidden">US electricity data by time period</h2>
        <div role="tablist" aria-label="Select a time period">
          <button type="button" id="tab-day" role="tab" aria-controls="tab-panel-day" aria-selected="true" data-period="past-day" tabindex="0"><span>Past </span>day</button>
          <button type="button" id="tab-week" role="tab" aria-controls="tab-panel-week" aria-selected="false" data-period="past-week" tabindex="-1"><span>Past </span>week</button>
          <button type="button" id="tab-year" role="tab" aria-controls="tab-panel-year" aria-selected="false" data-period="past-year" tabindex="-1"><span>Past </span>year</button>
          <button type="button" id="tab-all" role="tab" aria-controls="tab-panel-all" aria-selected="false" data-period="all-time" tabindex="-1">All<span> time</span></button>
        </div>
        <p class="history-lede">
          Compare recent US demand, generation and net cross-border flow over the past day or week. Past year and all time use the longer-running generation series; full-range demand and interchange history has not yet been loaded on this site.
        </p>
<?php

    foreach (self::PERIODS as $period) {
      $series = self::periodSeries($history, $period[3]);

      self::outputPanel(
        $period[0],
        $period[1],
        $period[2],
        $state,
        $period[3],
        $series,
        self::summarySeries($series, $historicalHistory, $period[7]),
        $period[4],
        $period[5],
        $period[6],
        $period[8]
      );
    }

?>
      </section>
<?php
  }

  private static function outputPanel(
    string  $id,
    string  $labelledBy,
    string  $title,
    UsState $state,
    ?int    $seconds,
    array   $series,
    array   $summarySeries,
    int     $timeStep,
    string  $timeFormat,
    int     $averageSeconds,
    bool    $showOperations
  ): void {
    $generationSeries = $showOperations
      ? ($summarySeries ?: $series)
      : $summarySeries;
    $generationMap = self::averageGeneration($generationSeries);
    $generation = array_sum($generationMap);
    $hasGeneration = $generationSeries && $generation > 0;
    $sourceRows = self::getSourceRows($generationMap);
    $typeRows = self::getTypeRows($generationMap);
    $operationHistory = self::getOperationHistory($state);
    $demandHistory = self::fieldSeries($operationHistory, 'demand');
    $flowHistory = self::fieldSeries($operationHistory, 'net_imports');
    $balanceHistory = self::completeSeries(
      $operationHistory,
      ['demand', 'generation', 'net_imports']
    );
    $demandSeries = self::periodSeries(
      $demandHistory,
      $seconds
    );
    $flowSeries = self::periodSeries($flowHistory, $seconds);
    $balanceSeries = self::periodSeries($balanceHistory, $seconds);
    $hasDemandCoverage = $showOperations
      && self::hasPeriodCoverage($demandSeries, $seconds);
    $hasFlowCoverage = $showOperations
      && self::hasPeriodCoverage($flowSeries, $seconds);
    $hasBalanceCoverage = $showOperations
      && self::hasPeriodCoverage($balanceSeries, $seconds);
    $equation = $hasBalanceCoverage
      ? self::averageBalanceSummary($balanceSeries)
      : [];
    $graphSeries = self::averageFuelSeries(
      $generationSeries,
      $averageSeconds
    );
    $demandGraphSeries = self::averageOperationSeries(
      $demandSeries,
      $averageSeconds
    );
    $flowGraphSeries = self::averageOperationSeries(
      $flowSeries,
      $averageSeconds
    );
?>
        <div id="<?= $id ?>" role="tabpanel" aria-labelledby="<?= $labelledBy ?>" tabindex="0">
          <div>
<?php UsStatus::output($state, $title); ?>
          </div>
          <div>
<?php UsEquation::output($state, $equation); ?>
          </div>
          <div>
<?php
  if ($hasGeneration) {
    UsPieChart::output($sourceRows, $typeRows, $generation);
  } else {
    UsGraph::outputUnavailable('Generation history is unavailable for this period');
  }
?>
          </div>
          <div>
<?php if ($hasGeneration) { ?>
            <h3>Generation by type</h3>
<?php self::outputRows($typeRows, $generation, $title . ' generation by type'); ?>
            <h3>Generation by source</h3>
<?php self::outputRows($sourceRows, $generation, $title . ' generation by source'); ?>
<?php } else { ?>
<?php UsGraph::outputUnavailable('Generation history is unavailable for this period'); ?>
<?php } ?>
          </div>
          <div>
            <h3>Price per MWh</h3>
<?php UsGraph::outputUnavailable('No US price feed in the current EIA data'); ?>
          </div>
          <div>
            <h3>Emissions per kWh</h3>
<?php UsGraph::outputUnavailable('No US carbon-intensity feed in the current EIA data'); ?>
          </div>
          <div>
            <h3>Demand</h3>
<?php
  if ($hasDemandCoverage && self::hasField($demandGraphSeries, 'demand')) {
    UsGraph::outputField(
      $demandGraphSeries,
      'demand',
      'demand',
      'GW',
      $timeStep,
      $timeFormat,
      1
    );
  } else {
    UsGraph::outputUnavailable(
      'Full-range US demand history is not loaded for this period'
    );
  }
?>
          </div>
          <div>
            <h3>Generation</h3>
<?php
  if ($hasGeneration) {
    UsGraph::outputSources($graphSeries, 'GW', $timeStep, $timeFormat, 2);
  } else {
    UsGraph::outputUnavailable('Generation history is unavailable for this period');
  }
?>
          </div>
          <div>
            <h3>Net cross-border flow</h3>
<?php
  if ($hasFlowCoverage && self::hasField($flowGraphSeries, 'net_imports')) {
    UsGraph::outputField(
      $flowGraphSeries,
      'net_imports',
      'transfers',
      'GW',
      $timeStep,
      $timeFormat,
      1,
      true
    );
    self::outputFlowCoverage($flowSeries);
  } else {
    UsGraph::outputUnavailable(
      $showOperations
        ? 'Recent cross-border flow is temporarily incomplete for this period.'
        : 'Cross-border history is not yet loaded for this range. Select Past day or Past week for recent flows.'
    );
  }
?>
          </div>
        </div>
<?php
  }

  private static function getHistory(UsState $state): array {
    $history = $state->latest['history'] ?? [];

    if (!$history) {
      return self::latestSeries($state);
    }

    usort(
      $history,
      static fn ($a, $b) => ((int)$a['timestamp']) <=> ((int)$b['timestamp'])
    );

    return $history;
  }

  private static function latestSeries(UsState $state): array {
    $timestamp = strtotime((string)($state->latest['time'] ?? ''));

    if ($timestamp === false) {
      $timestamp = time();
    }

    $generation = array_map(
      static fn ($value) => max(0.0, (float)$value),
      $state->latest['generation'] ?? []
    );

    return [[
      'time' => (string)($state->latest['time'] ?? ''),
      'timestamp' => $timestamp,
      'generation' => $generation,
      'total' => array_sum($generation),
    ]];
  }

  private static function getOperationHistory(UsState $state): array {
    $history = $state->latest['operations']['history'] ?? [];

    if (!is_array($history)) {
      return [];
    }

    $history = array_values(array_filter(
      $history,
      static fn ($point) => isset($point['timestamp'])
    ));

    usort(
      $history,
      static fn ($a, $b) => ((int)$a['timestamp']) <=> ((int)$b['timestamp'])
    );

    return $history;
  }

  private static function fieldSeries(array $history, string $field): array {
    return array_values(array_filter(
      $history,
      static fn ($point) => isset($point['timestamp'], $point[$field])
    ));
  }

  private static function completeSeries(array $history, array $fields): array {
    return array_values(array_filter(
      $history,
      static function ($point) use ($fields): bool {
        if (!isset($point['timestamp'])) {
          return false;
        }

        foreach ($fields as $field) {
          if (!isset($point[$field])) {
            return false;
          }
        }

        return true;
      }
    ));
  }

  private static function hasPeriodCoverage(
    array $series,
    ?int  $seconds
  ): bool {
    if ($seconds === null || count($series) < 2) {
      return false;
    }

    $timestamps = array_map(
      static fn ($point) => (int)$point['timestamp'],
      $series
    );

    sort($timestamps);

    if (max($timestamps) - min($timestamps) < max(0, $seconds - 7200)) {
      return false;
    }

    $minimumSamples = max(2, (int)floor(($seconds / 3600) * 0.75));

    if (count($timestamps) < $minimumSamples) {
      return false;
    }

    for ($index = 1; $index < count($timestamps); $index ++) {
      if ($timestamps[$index] - $timestamps[$index - 1] > 14400) {
        return false;
      }
    }

    return true;
  }

  private static function averageBalanceSummary(array $series): array {
    $series = self::completeSeries(
      $series,
      ['demand', 'generation', 'net_imports']
    );

    if (!$series) {
      return [];
    }

    $sums = [
      'demand' => 0.0,
      'generation' => 0.0,
      'net_imports' => 0.0,
    ];

    foreach ($series as $point) {
      foreach ($sums as $field => $sum) {
        $sums[$field] += (float)$point[$field];
      }
    }

    $count = count($series);

    return array_map(
      static fn ($sum) => $sum / $count,
      $sums
    );
  }

  private static function outputFlowCoverage(array $series): void {
    if (!$series) {
      return;
    }

    $first = (int)$series[0]['timestamp'];
    $last = (int)$series[count($series) - 1]['timestamp'];
?>
            <p class="transfer-coverage">
              Coverage: <?= gmdate('j M Y, g:ia', $first) ?>&ndash;<?= gmdate('j M Y, g:ia', $last) ?> UTC.
              Above zero shows net imports; below zero shows net exports. Missing reporting hours are omitted.
            </p>
<?php
  }

  private static function getHistoricalHistory(UsState $state): array {
    $history = $state->latest['historical_generation']['history'] ?? [];

    if (!is_array($history)) {
      return [];
    }

    $history = array_values(array_filter(
      $history,
      static fn ($point) => isset($point['timestamp'], $point['generation'])
    ));

    usort(
      $history,
      static fn ($a, $b) => ((int)$a['timestamp']) <=> ((int)$b['timestamp'])
    );

    return $history;
  }

  private static function summarySeries(
    array  $liveSeries,
    array  $historicalSeries,
    string $range
  ): array {
    if ($range === 'live') {
      return $liveSeries;
    }

    if (!$historicalSeries) {
      return [];
    }

    if ($range === 'year') {
      return array_slice($historicalSeries, -12);
    }

    return $historicalSeries;
  }

  private static function hasField(array $series, string $field): bool {
    foreach ($series as $point) {
      if (isset($point[$field])) {
        return true;
      }
    }

    return false;
  }

  private static function averageFuelSeries(array $series, int $seconds): array {
    if ($seconds <= 0 || count($series) < 2) {
      return $series;
    }

    $buckets = [];

    foreach ($series as $point) {
      $timestamp = (int)($point['timestamp'] ?? 0);

      if ($timestamp <= 0) {
        continue;
      }

      $bucket = (int)(floor($timestamp / $seconds) * $seconds);

      if (!isset($buckets[$bucket])) {
        $buckets[$bucket] = [
          'timestamp' => $bucket,
          'generation' => self::emptyGeneration(),
          'count' => 0,
        ];
      }

      foreach ($buckets[$bucket]['generation'] as $key => $value) {
        $buckets[$bucket]['generation'][$key] += max(
          0.0,
          (float)($point['generation'][$key] ?? 0)
        );
      }

      $buckets[$bucket]['count'] ++;
    }

    ksort($buckets);

    $averages = [];

    foreach ($buckets as $bucket) {
      $count = max(1, (int)$bucket['count']);

      foreach ($bucket['generation'] as $key => $value) {
        $bucket['generation'][$key] = $value / $count;
      }

      $bucket['total'] = array_sum($bucket['generation']);
      unset($bucket['count']);
      $averages[] = $bucket;
    }

    return $averages ?: $series;
  }

  private static function averageOperationSeries(
    array $series,
    int   $seconds
  ): array {
    if ($seconds <= 0 || count($series) < 2) {
      return $series;
    }

    $fields = [
      'demand',
      'generation',
      'net_imports',
      'interchange',
      'canada',
      'mexico',
    ];
    $buckets = [];

    foreach ($series as $point) {
      $timestamp = (int)($point['timestamp'] ?? 0);

      if ($timestamp <= 0) {
        continue;
      }

      $bucket = (int)(floor($timestamp / $seconds) * $seconds);

      if (!isset($buckets[$bucket])) {
        $buckets[$bucket] = [
          'timestamp' => $bucket,
          'sums' => [],
          'counts' => [],
        ];
      }

      foreach ($fields as $field) {
        if (!isset($point[$field])) {
          continue;
        }

        $buckets[$bucket]['sums'][$field] =
          ($buckets[$bucket]['sums'][$field] ?? 0)
          + (float)$point[$field];
        $buckets[$bucket]['counts'][$field] =
          ($buckets[$bucket]['counts'][$field] ?? 0) + 1;
      }
    }

    ksort($buckets);

    $averages = [];

    foreach ($buckets as $timestamp => $bucket) {
      $point = ['timestamp' => (int)$timestamp];

      foreach ($bucket['sums'] as $field => $sum) {
        $point[$field] = $sum / max(1, (int)$bucket['counts'][$field]);
      }

      if (count($point) > 1) {
        $averages[] = $point;
      }
    }

    return $averages ?: $series;
  }

  private static function periodSeries(array $history, ?int $seconds): array {
    if (!$history || $seconds === null) {
      return $history;
    }

    $latest = max(array_map(
      static fn ($point) => (int)$point['timestamp'],
      $history
    ));
    $cutoff = $latest - $seconds;
    $series = array_values(array_filter(
      $history,
      static fn ($point) => (int)$point['timestamp'] >= $cutoff
    ));

    return $series ?: $history;
  }

  private static function averageGeneration(array $series): array {
    $generation = self::emptyGeneration();
    $totalWeight = 0.0;

    foreach ($series as $point) {
      $weight = max(0.0, (float)($point['weight'] ?? 1));

      if ($weight <= 0) {
        continue;
      }

      foreach ($generation as $key => $value) {
        $generation[$key] += max(0.0, (float)(
          $point['generation'][$key] ?? 0
        )) * $weight;
      }

      $totalWeight += $weight;
    }

    $totalWeight = max(1.0, $totalWeight);

    foreach ($generation as $key => $value) {
      $generation[$key] = $value / $totalWeight;
    }

    return $generation;
  }

  private static function emptyGeneration(): array {
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

  private static function getTypeRows(array $generation): array {
    return array_values(array_filter([
      [
        'class' => 'fossils',
        'label' => 'Fossil fuels',
        'power' => (float)($generation['coal'] ?? 0)
          + (float)($generation['gas'] ?? 0)
          + (float)($generation['oil'] ?? 0)
      ],
      [
        'class' => 'renewables',
        'label' => 'Renewables',
        'power' => (float)($generation['solar'] ?? 0)
          + (float)($generation['wind'] ?? 0)
          + (float)($generation['hydro'] ?? 0)
      ],
      [
        'class' => 'others',
        'label' => 'Other sources',
        'power' => (float)($generation['nuclear'] ?? 0)
          + (float)($generation['biomass'] ?? 0)
          + (float)($generation['other'] ?? 0)
      ]
    ], fn ($row) => $row['power'] > 0.00001));
  }

  private static function getSourceRows(array $generation): array {
    $rows = [];

    foreach (self::SOURCE_ORDER as $key) {
      $power = max(0.0, (float)($generation[$key] ?? 0));

      if ($power < 0.00001) {
        continue;
      }

      $rows[] = [
        'class' => self::SOURCE_META[$key]['class'] ?? 'others',
        'label' => self::SOURCE_META[$key]['label'] ?? ucfirst((string)$key),
        'power' => $power
      ];
    }

    return $rows;
  }

  private static function outputRows(
    array  $rows,
    float  $generation,
    string $caption
  ): void {
?>
            <table class="sources">
              <caption class="visually-hidden"><?= htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') ?></caption>
<?php

    foreach ($rows as $row) {
      echo '              <tr><td class="';
      echo htmlspecialchars($row['class'], ENT_QUOTES, 'UTF-8');
      echo '"></td><th scope="row">';
      echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8');
      echo '</th><td>';
      echo Value::formatPower((float)$row['power']);
      echo '</td><td>';
      echo Value::formatPercentage($generation > 0 ? ((float)$row['power'] / $generation) : 0);
      echo "</td></tr>\n";
    }

?>
            </table>
<?php
  }
}
