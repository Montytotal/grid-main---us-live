<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\UsState;

class UsTabs {
  private const PERIODS = [
    ['tab-panel-day', 'tab-day', 'Past day', 86400, 6, 'g:ia', 0],
    ['tab-panel-week', 'tab-week', 'Past week', 604800, 24, 'D', 21600],
    ['tab-panel-year', 'tab-year', 'Past year', 31536000, 168, 'j M', 86400],
    ['tab-panel-all', 'tab-all', 'All time', null, 168, 'Y', 86400],
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
?>
      <section>
        <div role="tablist">
          <h2 id="tab-day" role="tab" aria-controls="tab-panel-day" aria-selected="true"><span>Past </span>day</h2>
          <h2 id="tab-week" role="tab" aria-controls="tab-panel-week" aria-selected="false"><span>Past </span>week</h2>
          <h2 id="tab-year" role="tab" aria-controls="tab-panel-year" aria-selected="false"><span>Past </span>year</h2>
          <h2 id="tab-all" role="tab" aria-controls="tab-panel-all" aria-selected="false">All<span> time</span></h2>
        </div>
<?php

    foreach (self::PERIODS as $period) {
      self::outputPanel(
        $period[0],
        $period[1],
        $period[2],
        $state,
        $period[3],
        self::periodSeries($history, $period[3]),
        $period[4],
        $period[5],
        $period[6]
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
    int     $timeStep,
    string  $timeFormat,
    int     $averageSeconds
  ): void {
    $generationMap = self::averageGeneration($series);
    $generation = array_sum($generationMap);
    $sourceRows = self::getSourceRows($generationMap);
    $typeRows = self::getTypeRows($generationMap);
    $operationSeries = self::periodSeries(
      self::getOperationHistory($state),
      $seconds
    );
    $graphSeries = self::averageFuelSeries($series, $averageSeconds);
    $operationGraphSeries = self::averageOperationSeries(
      $operationSeries,
      $averageSeconds
    );
?>
        <div id="<?= $id ?>" role="tabpanel" aria-labelledby="<?= $labelledBy ?>" tabindex="0">
          <div>
<?php UsStatus::output($state, $title); ?>
          </div>
          <div>
<?php UsEquation::output($state, $generation); ?>
          </div>
          <div>
<?php UsPieChart::output($sourceRows, $typeRows, $generation); ?>
          </div>
          <div>
            <h3>Generation by type</h3>
<?php self::outputRows($typeRows, $generation); ?>
            <h3>Generation by source</h3>
<?php self::outputRows($sourceRows, $generation); ?>
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
  if (self::hasField($operationGraphSeries, 'demand')) {
    UsGraph::outputField(
      $operationGraphSeries,
      'demand',
      'demand',
      'GW',
      $timeStep,
      $timeFormat,
      1
    );
  } else {
    UsGraph::outputTotal($graphSeries, 'demand', 'GW', $timeStep, $timeFormat, 1);
  }
?>
          </div>
          <div>
            <h3>Generation</h3>
<?php UsGraph::outputSources($graphSeries, 'GW', $timeStep, $timeFormat, 2); ?>
          </div>
          <div>
            <h3>Transfers</h3>
<?php
  if (
    self::hasField($operationGraphSeries, 'canada')
    || self::hasField($operationGraphSeries, 'mexico')
  ) {
    UsGraph::outputFields(
      $operationGraphSeries,
      [
        'canada' => 'canada',
        'mexico' => 'mexico',
        'transfers' => 'transfers',
      ],
      'GW',
      $timeStep,
      $timeFormat,
      1,
      true
    );
  } elseif (self::hasField($operationGraphSeries, 'transfers')) {
    UsGraph::outputField(
      $operationGraphSeries,
      'transfers',
      'transfers',
      'GW',
      $timeStep,
      $timeFormat,
      1,
      true
    );
  } else {
    UsGraph::outputUnavailable('No US transfer series is available yet');
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
      'transfers',
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

    foreach ($series as $point) {
      foreach ($generation as $key => $value) {
        $generation[$key] += max(
          0.0,
          (float)($point['generation'][$key] ?? 0)
        );
      }
    }

    $count = max(1, count($series));

    foreach ($generation as $key => $value) {
      $generation[$key] = $value / $count;
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

  private static function outputRows(array $rows, float $generation): void {
?>
            <table class="sources">
<?php

    foreach ($rows as $row) {
      echo '              <tr><td class="';
      echo htmlspecialchars($row['class'], ENT_QUOTES, 'UTF-8');
      echo '"></td><td>';
      echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8');
      echo '</td><td>';
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
