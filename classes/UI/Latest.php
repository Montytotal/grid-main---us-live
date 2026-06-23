<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\Datum;
use KateMorley\Grid\State\Generation;
use KateMorley\Grid\State\Interconnectors;
use KateMorley\Grid\State\Map;
use KateMorley\Grid\State\Storage;
use KateMorley\Grid\State\Types;
use KateMorley\Grid\UI\PieChart;

/** Outputs the latest data. */
class Latest {
  /**
   * Outputs the latest data.
   *
   * @param Datum $datum The datum
   */
  public static function output(Datum $datum): void {
?>
      <div id="latest">
        <section id="generation">
          <h2>Generation</h2>
          <div class="pie-chart-container">
            <?php PieChart::output($datum); ?>
          </div>
          <div>
            Note: percentages are relative to demand, so will exceed 100% if power is being exported
          </div>
        </section>
<?php

    $demand = $datum->getTotal();

?>
        <section id="fossils">
          <h2><?= Value::formatPercentage($datum->types->get(Types::FOSSILS) / $demand) ?>% fossil fuels</h2>
<?php

    self::outputTable($datum->generation, [
      Generation::GAS  => '<p>Gas-fired power stations burn natural gas to drive a turbine. Most gas-fired power stations use the excess heat from burning the gas to produce steam to drive a second turbine. Burning natural gas causes carbon dioxide and other pollutants to be emitted.</p>'
    ], $demand);

?>
        </section>
        <section id="renewables">
          <h2><?= Value::formatPercentage($datum->types->get(Types::RENEWABLES) / $demand) ?>% renewables</h2>
<?php

    self::outputTable($datum->generation, [
      Generation::SOLAR         => '<p>Solar panels generate power from the photovoltaic effect.</p>',
      Generation::WIND          => '<p>Wind turbines generate power from the movement of air.</p>',
      Generation::HYDROELECTRIC => '<p>Hydroelectric turbines generate power from the movement of water.</p>'
    ], $demand);

?>
        </section>
        <section id="others">
          <h2><?= Value::formatPercentage($datum->types->get(Types::OTHERS) / $demand) ?>% other sources</h2>
<?php

    self::outputTable($datum->generation, [
      Generation::NUCLEAR => '<p>Nuclear power stations use the heat produced from the radioactive decay of uranium to produce steam to drive a turbine.</p>',
      Generation::BIOMASS => '<p>Biomass power stations burn plant material to produce steam to drive a turbine.</p>'
    ], $demand);

?>
        </section>
        <section id="transfers">
          <h2><?= Value::formatPercentage($datum->interconnectors->getTotal() / $demand) ?>% interconnectors</h2>
<?php

    self::outputInterconnectorMap($datum->interconnectors, $demand);

    self::outputTable($datum->interconnectors, [
      Interconnectors::BELGIUM     => '<p>There is one link between Great Britain and Belgium.</p>',
      Interconnectors::DENMARK     => '<p>There is one link between Great Britain and Denmark.</p>',
      Interconnectors::FRANCE      => '<p>There are multiple links between Great Britain and France.</p>',
      Interconnectors::IRELAND     => '<p>There are multiple links between Great Britain and the island of Ireland.</p>',
      Interconnectors::NETHERLANDS => '<p>There is one link between Great Britain and the Netherlands.</p>',
      Interconnectors::NORWAY      => '<p>There is one link between Great Britain and Norway.</p>'
    ], $demand, true);

?>
        </section>
        <section id="storage">
          <h2><?= Value::formatPercentage($datum->storage->getTotal() / $demand) ?>% storage</h2>
<?php

    self::outputTable($datum->storage, [
      Storage::PUMPED_STORAGE => '<p>Pumped storage systems use electricity when it is comparatively cheap to pump water from a lower reservoir into a higher reservoir.</p>',
      'battery' => '<p>Battery storage systems store electricity chemically for later discharge.</p>'
    ], $demand, true);

?>
        </section>
      </div>
<?php
  }

  /**
   * Outputs a live interconnector map.
   *
   * @param Interconnectors $map    The interconnector values
   * @param float           $demand The total demand
   */
  private static function outputInterconnectorMap(
    Interconnectors $map,
    float           $demand
  ): void {
    $connectors = [
      Interconnectors::IRELAND => [
        'path'     => 'M152 173 Q114 170 76 171',
        'label_x'  => 10,
        'label_y'  => 147,
        'node_x'   => 76,
        'node_y'   => 171
      ],
      Interconnectors::FRANCE => [
        'path'     => 'M262 246 Q332 244 395 255',
        'label_x'  => 322,
        'label_y'  => 232,
        'node_x'   => 395,
        'node_y'   => 255
      ],
      Interconnectors::BELGIUM => [
        'path'     => 'M292 176 Q355 167 430 182',
        'label_x'  => 338,
        'label_y'  => 156,
        'node_x'   => 430,
        'node_y'   => 182
      ],
      Interconnectors::NETHERLANDS => [
        'path'     => 'M302 152 Q366 145 444 142',
        'label_x'  => 344,
        'label_y'  => 118,
        'node_x'   => 444,
        'node_y'   => 142
      ],
      Interconnectors::NORWAY => [
        'path'     => 'M302 90 Q350 55 410 38',
        'label_x'  => 326,
        'label_y'  => 16,
        'node_x'   => 410,
        'node_y'   => 38
      ],
      Interconnectors::DENMARK => [
        'path'     => 'M322 120 Q388 92 458 96',
        'label_x'  => 366,
        'label_y'  => 72,
        'node_x'   => 458,
        'node_y'   => 96
      ]
    ];
?>
          <div class="interconnector-map">
            <svg viewBox="0 0 520 340" role="img" aria-labelledby="interconnector-map-title">
              <title id="interconnector-map-title">Live electricity imports and exports between Great Britain and neighbouring countries</title>

              <path class="uk-shape" d="M180 49 L194 43 L210 51 L224 49 L238 60 L252 79 L249 95 L259 111 L252 126 L262 143 L252 160 L247 178 L252 196 L244 211 L229 220 L218 238 L202 249 L190 242 L184 227 L171 214 L160 197 L145 182 L149 165 L141 148 L146 131 L158 118 L157 99 L167 84 L162 66 Z"></path>
              <path class="ireland-shape" d="M92 137 L108 129 L122 133 L129 147 L126 162 L117 173 L103 176 L91 167 L87 151 Z"></path>

              <text class="map-gb-label" x="170" y="104">Great Britain</text>
              <text class="map-ie-label" x="42" y="141">Ireland</text>

<?php
    foreach ($connectors as $key => $connector) {
      self::outputInterconnectorMapLine($key, $connector, $map, $demand);
    }
?>
            </svg>

            <div class="interconnector-map-legend">
              <span><span class="legend-dot legend-import-dot"></span> Imports to Great Britain</span>
              <span><span class="legend-dot legend-export-dot"></span> Exports from Great Britain</span>
            </div>
          </div>
<?php
  }

  /**
   * Outputs a single interconnector path and label.
   *
   * @param string          $key       The connector key
   * @param array           $connector The connector geometry
   * @param Interconnectors $map       The interconnector values
   * @param float           $demand    The total demand
   */
  private static function outputInterconnectorMapLine(
    string          $key,
    array           $connector,
    Interconnectors $map,
    float           $demand
  ): void {
    $power = $map->get($key);
    $percentage = ($demand > 0 ? abs($power) / $demand : 0);
    $direction = ($power > 0 ? 'Import' : ($power < 0 ? 'Export' : 'Flow'));
    $directionClass = ($power > 0 ? 'is-import' : ($power < 0 ? 'is-export' : 'is-neutral'));
    $color = self::getInterconnectorMapColor($power, $demand);
?>
              <g class="interconnector-line <?= $directionClass ?>" style="--connector-color: <?= $color ?>;">
                <title><?= Interconnectors::KEYS[$key] ?>: <?= $direction ?> <?= Value::formatPower(abs($power)) ?>GW (<?= Value::formatPercentage($percentage) ?>%)</title>

                <path class="connector-base" d="<?= $connector['path'] ?>"></path>
                <path class="connector-flow" d="<?= $connector['path'] ?>"></path>

                <circle class="connector-node" cx="<?= $connector['node_x'] ?>" cy="<?= $connector['node_y'] ?>" r="5"></circle>

                <g class="connector-label" transform="translate(<?= $connector['label_x'] ?> <?= $connector['label_y'] ?>)">
                  <rect width="150" height="46" rx="12"></rect>
                  <text x="10" y="17" class="connector-country"><?= Interconnectors::KEYS[$key] ?></text>
                  <text x="10" y="33" class="connector-value"><?= $direction ?> <?= Value::formatPower(abs($power)) ?>GW · <?= Value::formatPercentage($percentage) ?>%</text>
                </g>
              </g>
<?php
  }

  /**
   * Returns the connector colour.
   *
   * @param float $power  The connector power
   * @param float $demand The total demand
   */
  private static function getInterconnectorMapColor(float $power, float $demand): string {
    if ($demand <= 0 || $power == 0) {
      return '#94a3b8';
    }

    $t = min((abs($power) / $demand) / 0.05, 1.0);

    if ($power > 0) {
      return self::interpolateColor([245, 158, 11], [220, 38, 38], $t);
    }

    return self::interpolateColor([96, 165, 250], [37, 99, 235], $t);
  }

  /**
   * Interpolates between two RGB colours.
   *
   * @param array $from Start RGB
   * @param array $to   End RGB
   * @param float $t    Blend amount
   */
  private static function interpolateColor(array $from, array $to, float $t): string {
    $r = (int) round($from[0] + (($to[0] - $from[0]) * $t));
    $g = (int) round($from[1] + (($to[1] - $from[1]) * $t));
    $b = (int) round($from[2] + (($to[2] - $from[2]) * $t));

    return sprintf('#%02x%02x%02x', $r, $g, $b);
  }

  /**
   * Outputs a table.
   *
   * @param Map           $map         The map
   * @param array<string> $keys        An array mapping keys to help
   * @param float         $demand      The total demand
   * @param bool          $isTransfers Whether the table shows transfers
   */
  private static function outputTable(
    Map   $map,
    array $keys,
    float $demand,
    bool  $isTransfers = false
  ): void {
?>
          <table class="sources<?= ($isTransfers ? ' transfers' : '') ?>">
<?php

    foreach ($keys as $key => $help) {
      echo '            <tr><td class="';
      echo $key;
      echo '"><td>';

      if ($key === 'battery') {
        echo 'Battery storage';
      } else {
        echo $map::KEYS[$key];
      }

      echo ' <span data-help="';
      echo $help;
      echo '"></span></td><td>';

      if ($key === 'battery') {
        echo '—';
      } else {
        echo Value::formatPower($map->get($key));
      }

      echo '</td><td>';

      if ($key === 'battery') {
        echo '—';
      } else {
        echo Value::formatPercentage($map->get($key) / $demand);
      }

      echo "</td></tr>\n";
    }

?>
          </table>
<?php
  }
}