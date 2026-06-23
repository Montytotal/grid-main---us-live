<?php

namespace KateMorley\Grid\UI;

class UsPieChart {
  private const OUTER_RADIUS = 0.75;
  private const INNER_RADIUS = 0.50;

  /**
   * Outputs a US-specific pie chart using ordered source and type rows.
   *
   * @param array $sourceRows Ordered generation-by-source rows
   * @param array $typeRows   Ordered generation-by-type rows
   * @param float $generation Total displayed generation
   */
  public static function output(
    array $sourceRows,
    array $typeRows,
    float $generation
  ): void {
    if ($generation <= 0) {
      echo '<div class="pie-chart"><div><div>Generation</div><div class="generation"></div><div><span>0.0</span>GW</div><div><span>0.0</span>%</div></div><svg viewBox="-1 -1 2 2"></svg></div>';
      return;
    }

    $generationPower = Value::formatTotalPower($generation);
    $generationPercentage = Value::formatPercentage(1);

    echo '<div class="pie-chart"><div><div>Generation</div><div class="generation"></div><div><span>';
    echo $generationPower;
    echo '</span>GW</div><div><span>';
    echo $generationPercentage;
    echo '</span>%</div></div><svg viewBox="-1 -1 2 2" data-power="';
    echo $generationPower;
    echo '" data-percentage="';
    echo $generationPercentage;
    echo '">';

    self::outputRing(
      $sourceRows,
      $generation,
      $generation,
      self::OUTER_RADIUS,
      1,
      false
    );

    self::outputRing(
      $typeRows,
      $generation,
      $generation,
      self::INNER_RADIUS,
      self::OUTER_RADIUS,
      true
    );

    echo "</svg></div>\n";
  }

  private static function outputRing(
    array $rows,
    float $generation,
    float $total,
    float $innerRadius,
    float $outerRadius,
    bool  $isTotal = false
  ): void {
    $offset = 0.0;

    foreach ($rows as $row) {
      $power = (float)($row['power'] ?? 0);

      if ($power <= 0) {
        continue;
      }

      $fraction = $power / $generation;

      self::outputArc(
        (string)($row['class'] ?? 'others'),
        ($isTotal ? Value::formatTotalPower($power) : Value::formatPower($power)),
        Value::formatPercentage($total > 0 ? ($power / $total) : 0),
        $fraction,
        $offset,
        $innerRadius,
        $outerRadius
      );

      $offset += $fraction;
    }
  }

  private static function outputArc(
    string $source,
    string $power,
    string $percentage,
    float  $fraction,
    float  $offset,
    float  $innerRadius,
    float  $outerRadius
  ): void {
    echo '<path class="';
    echo htmlspecialchars($source, ENT_QUOTES, 'UTF-8');
    echo '" d="M';
    self::outputArcPoint($offset, $outerRadius);
    echo 'A';
    echo $outerRadius;
    echo ',';
    echo $outerRadius;
    echo ' 0 ';
    echo ($fraction < 0.5 ? 0 : 1);
    echo ' 1 ';
    self::outputArcPoint($offset + $fraction, $outerRadius);
    echo 'L';
    self::outputArcPoint($offset + $fraction, $innerRadius);
    echo 'A';
    echo $innerRadius;
    echo ',';
    echo $innerRadius;
    echo ' 0 ';
    echo ($fraction < 0.5 ? 0 : 1);
    echo ' 0 ';
    self::outputArcPoint($offset, $innerRadius);
    echo 'z" data-power="';
    echo htmlspecialchars($power, ENT_QUOTES, 'UTF-8');
    echo '" data-percentage="';
    echo htmlspecialchars($percentage, ENT_QUOTES, 'UTF-8');
    echo '"/>';
  }

  private static function outputArcPoint(float $fraction, float $radius): void {
    printf('%0.4f', $radius * sin($fraction * 2 * M_PI));
    echo ',';
    printf('%0.4f', $radius * -cos($fraction * 2 * M_PI));
  }
}
