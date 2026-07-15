<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\UsState;

class UsLatestSection {
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
    $generation = (float)($state->view['summary']['generation'] ?? 0);

    $typeRows = self::getTypeRows($state);
    $sourceRows = self::getSourceRows($state);

    if ($generation <= 0) {
      $generation = array_sum(array_map(
        fn ($row) => (float)$row['power'],
        $sourceRows
      ));
    }
?>
      <div id="latest">
        <section id="generation">
          <h2>Latest generation mix</h2>
          <div class="pie-chart-container">
            <?php UsPieChart::output($sourceRows, $typeRows, $generation); ?>
          </div>
          <div class="generation-notes">
            <p>Percentages are shares of displayed generation.</p>
<?php self::outputOtherSourcesNote($sourceRows); ?>
          </div>
        </section>

        <section id="fossils">
          <h2><?= self::formatGenerationPercentage(self::getRowPower($typeRows, 'fossils'), $generation) ?>% fossil fuels</h2>
<?php self::outputRows(self::getRowsByClasses($sourceRows, ['coal', 'gas', 'oil']), $generation, 'Fossil fuel generation'); ?>
        </section>

        <section id="renewables">
          <h2><?= self::formatGenerationPercentage(self::getRowPower($typeRows, 'renewables'), $generation) ?>% renewables</h2>
<?php self::outputRows(self::getRowsByClasses($sourceRows, ['solar', 'wind', 'hydro']), $generation, 'Renewable electricity generation'); ?>
        </section>

        <section id="others">
          <h2><?= self::formatGenerationPercentage(self::getRowPower($typeRows, 'others'), $generation) ?>% other sources</h2>
<?php self::outputRows(self::getRowsByClasses($sourceRows, ['nuclear', 'biomass', 'others']), $generation, 'Other electricity generation sources'); ?>
        </section>

        <section id="transfers">
          <h2>Net cross-border flow by country</h2>
<?php
    $transferData = self::getTransferData($state);

    if ($transferData['rows']) {
      self::outputTransferRows($transferData['rows']);
      self::outputTransferContext($transferData);
    } else {
      self::outputUnavailableRows([[
        'class' => 'transfers',
        'label' => 'Country breakdown'
      ]]);
    }
?>
        </section>

        <section id="storage">
          <h2>Storage</h2>
<?php self::outputUnavailableRows([['class' => 'battery', 'label' => 'Battery storage']], 'Battery storage availability'); ?>
        </section>
      </div>
<?php
  }

  private static function getTypeRows(UsState $state): array {
    $generation = array_map(
      fn ($value) => max(0.0, (float)$value),
      $state->latest['generation'] ?? []
    );

    $rows = [
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
    ];

    return array_values(array_filter(
      $rows,
      fn ($row) => $row['power'] > 0.00001
    ));
  }

  private static function getSourceRows(UsState $state): array {
    $meta = [
      'gas' => [
        'class' => 'gas',
        'label' => 'Gas'
      ],
      'nuclear' => [
        'class' => 'nuclear',
        'label' => 'Nuclear'
      ],
      'wind' => [
        'class' => 'wind',
        'label' => 'Wind'
      ],
      'hydro' => [
        'class' => 'hydro',
        'label' => 'Hydroelectric'
      ],
      'solar' => [
        'class' => 'solar',
        'label' => 'Solar'
      ],
      'coal' => [
        'class' => 'coal',
        'label' => 'Coal'
      ],
      'biomass' => [
        'class' => 'biomass',
        'label' => 'Biomass'
      ],
      'oil' => [
        'class' => 'oil',
        'label' => 'Oil'
      ],
      'other' => [
        'class' => 'others',
        'label' => 'Other'
      ]
    ];

    $rows = [];

    $generation = $state->latest['generation'] ?? [];

    foreach (self::SOURCE_ORDER as $key) {
      $power = max(0.0, (float)($generation[$key] ?? 0));

      if ($power < 0.00001) {
        continue;
      }

      $rows[] = [
        'class' => $meta[$key]['class'] ?? 'others',
        'label' => $meta[$key]['label'] ?? ucfirst((string)$key),
        'power' => $power
      ];
    }

    return $rows;
  }

  private static function getRowsByClasses(array $rows, array $classes): array {
    return array_values(array_filter(
      $rows,
      fn ($row) => in_array($row['class'], $classes, true)
    ));
  }

  private static function outputOtherSourcesNote(array $sourceRows): void {
    $rows = self::getRowsByClasses(
      $sourceRows,
      ['nuclear', 'biomass', 'others']
    );

    if (!$rows) {
      return;
    }

    $labels = array_values(array_map(
      static fn ($row) => (string)($row['label'] ?? ''),
      $rows
    ));
    $labels = array_values(array_filter($labels));

    if (!$labels) {
      return;
    }

    $list = self::formatList($labels);
    $hasOtherRow = (bool)array_filter(
      $rows,
      static fn ($row) => ($row['class'] ?? '') === 'others'
    );
?>
            <p>
              For this snapshot, &ldquo;Other sources&rdquo; <?= count($labels) > 1 ? 'combines' : 'contains' ?> <?= htmlspecialchars($list, ENT_QUOTES, 'UTF-8') ?>. A source appears as a separate row only when it has a positive reported value.
<?php if ($hasOtherRow) { ?>
              The &ldquo;Other&rdquo; row contains EIA-reported Other, geothermal and any unmapped fuel types.
<?php } ?>
            </p>
<?php
  }

  private static function formatList(array $labels): string {
    if (count($labels) < 2) {
      return (string)($labels[0] ?? '');
    }

    $last = array_pop($labels);

    return implode(', ', $labels) . ' and ' . $last;
  }

  private static function getRowPower(array $rows, string $class): float {
    foreach ($rows as $row) {
      if (($row['class'] ?? '') === $class) {
        return (float)$row['power'];
      }
    }

    return 0.0;
  }

  private static function outputRows(
    array  $rows,
    float  $generation,
    string $caption,
    bool   $isTotal = false
  ): void {
?>
          <table class="sources">
            <caption class="visually-hidden"><?= htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') ?></caption>
<?php

    foreach ($rows as $row) {
      echo '            <tr><td class="';
      echo htmlspecialchars($row['class'], ENT_QUOTES, 'UTF-8');
      echo '"></td><th scope="row">';
      echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8');
      echo '</th><td>';

      if ($isTotal) {
        echo Value::formatTotalPower((float)$row['power']);
      } else {
        echo Value::formatPower((float)$row['power']);
      }

      echo '</td><td>';
      echo self::formatGenerationPercentage((float)$row['power'], $generation);
      echo "</td></tr>\n";
    }

?>
          </table>
<?php
  }

  private static function outputUnavailableRows(
    array  $rows,
    string $caption = 'Unavailable electricity data'
  ): void {
?>
          <table class="sources">
            <caption class="visually-hidden"><?= htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') ?></caption>
<?php

    foreach ($rows as $row) {
      echo '            <tr><td class="';
      echo htmlspecialchars($row['class'], ENT_QUOTES, 'UTF-8');
      echo '"></td><th scope="row">';
      echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8');
      echo '</th><td>&mdash;</td><td>&mdash;</td></tr>';
      echo "\n";
    }

?>
          </table>
<?php
  }

  private static function getTransferData(UsState $state): array {
    $snapshot = $state->latest['operations']['latest']['country'] ?? [];
    $values = is_array($snapshot['values'] ?? null)
      ? $snapshot['values']
      : [];
    $rows = [];

    foreach ([
      'canada' => ['class' => 'canada', 'label' => 'Canada'],
      'mexico' => ['class' => 'mexico', 'label' => 'Mexico'],
    ] as $field => $meta) {
      if (!isset($values[$field])) {
        continue;
      }

      $rows[] = [
        'class' => $meta['class'],
        'label' => $meta['label'],
        'power' => (float)$values[$field],
      ];
    }

    if (isset($snapshot['total'])) {
      $rows[] = [
        'class' => 'transfers',
        'label' => 'Reported net country subtotal',
        'power' => (float)$snapshot['total'],
      ];
    }

    if (isset($snapshot['national_net_imports'])) {
      $rows[] = [
        'class' => 'transfers',
        'label' => 'US48 net total, same hour',
        'power' => (float)$snapshot['national_net_imports'],
      ];
    }

    $headline = is_array($state->view['equation'] ?? null)
      ? $state->view['equation']
      : [];

    return [
      'rows' => $rows,
      'timestamp' => (int)($snapshot['timestamp'] ?? 0),
      'both_countries' => (bool)($snapshot['both_countries'] ?? false),
      'national_same_hour' => isset($snapshot['national_net_imports']),
      'headline_timestamp' => (int)($headline['timestamp'] ?? 0),
    ];
  }

  private static function outputTransferRows(array $rows): void {
?>
          <table class="sources transfer-table">
            <caption class="visually-hidden">Latest reported US cross-border electricity transfers</caption>
<?php

    foreach ($rows as $row) {
      $power = (float)$row['power'];

      echo '            <tr><td class="';
      echo htmlspecialchars($row['class'], ENT_QUOTES, 'UTF-8');
      echo '"></td><th scope="row">';
      echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8');
      echo '</th><td>';
      echo Value::formatPower(abs($power));
      echo '</td><td>';
      echo $power >= 0 ? 'import' : 'export';
      echo "</td></tr>\n";
    }

?>
          </table>
<?php
  }

  private static function outputTransferContext(array $transferData): void {
    $timestamp = (int)($transferData['timestamp'] ?? 0);

    if ($timestamp <= 0) {
      return;
    }

    $bothCountries = (bool)($transferData['both_countries'] ?? false);
    $nationalSameHour = (bool)($transferData['national_same_hour'] ?? false);
    $headlineTimestamp = (int)($transferData['headline_timestamp'] ?? 0);
?>
          <p class="transfer-reporting-time">
            Latest hour with <?= $bothCountries ? 'both country entries' : 'available country data' ?>:
            <?= UsStatus::time($timestamp) ?>.
            The reported net country subtotal adds the direct-interchange rows for Canada and Mexico available for that hour<?= $bothCountries ? '' : '; one country entry is missing' ?>.
<?php if ($nationalSameHour) { ?>
            The US48 row is EIA&rsquo;s separately reported total net interchange for the same hour, providing a timestamp-aligned comparison.
<?php } ?>
            The two reports can still differ because EIA collects them on different schedules and they can have different checks, revisions and missing submissions.
          </p>
<?php if ($headlineTimestamp > 0) { ?>
          <p class="transfer-reporting-time">
            The headline uses <?= $headlineTimestamp === $timestamp ? 'that same' : 'a newer' ?> US48 reporting hour, <?= UsStatus::time($headlineTimestamp) ?>. It reads EIA total net interchange directly; it is not calculated by subtracting generation from demand.
          </p>
<?php } ?>
<?php
  }

  private static function formatGenerationPercentage(float $value, float $generation): string {
    if ($generation <= 0) {
      return Value::formatPercentage(0);
    }

    return Value::formatPercentage($value / $generation);
  }
}
