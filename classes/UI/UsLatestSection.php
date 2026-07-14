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
            <p>
              The &ldquo;Other sources&rdquo; group combines nuclear, biomass and the &ldquo;Other&rdquo; bucket. That bucket contains EIA-reported Other, geothermal and any unmapped fuel types.
            </p>
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
          <h2>Transfers</h2>
<?php
    $transferRows = self::getTransferRows($state);

    if ($transferRows) {
      self::outputTransferRows($transferRows);
    } else {
      self::outputUnavailableRows([[
        'class' => 'transfers',
        'label' => 'Regional flows'
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

  private static function getTransferRows(UsState $state): array {
    $latest = $state->latest['operations']['latest'] ?? [];
    $rows = [];

    foreach ([
      'canada' => ['class' => 'canada', 'label' => 'Canada'],
      'mexico' => ['class' => 'mexico', 'label' => 'Mexico'],
      'transfers' => ['class' => 'transfers', 'label' => 'Total'],
    ] as $field => $meta) {
      $point = $latest[$field] ?? null;

      if (!is_array($point) || !isset($point['value'])) {
        continue;
      }

      $rows[] = [
        'class' => $meta['class'],
        'label' => $meta['label'],
        'power' => (float)$point['value'],
      ];
    }

    return $rows;
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

  private static function formatGenerationPercentage(float $value, float $generation): string {
    if ($generation <= 0) {
      return Value::formatPercentage(0);
    }

    return Value::formatPercentage($value / $generation);
  }
}
