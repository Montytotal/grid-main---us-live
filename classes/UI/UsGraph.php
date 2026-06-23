<?php

namespace KateMorley\Grid\UI;

class UsGraph {
  private const SIZE = 500;

  private const SOURCES = [
    'coal' => 'coal',
    'gas' => 'gas',
    'oil' => 'oil',
    'solar' => 'solar',
    'wind' => 'wind',
    'hydro' => 'hydro',
    'nuclear' => 'nuclear',
    'biomass' => 'biomass',
    'other' => 'others',
  ];

  public static function outputUnavailable(string $message): void {
    echo '<div class="us-unavailable-graph"><div>';
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo "</div></div>\n";
  }

  public static function outputTotal(
    array  $series,
    string $class,
    string $suffix,
    int    $timeStep,
    string $timeFormat,
    int    $decimalPlaces
  ): void {
    $series = self::normaliseSeries($series);

    self::output(
      $series,
      [
        $class => array_map(
          static fn ($point) => (float)($point['total'] ?? 0),
          $series
        )
      ],
      '',
      $suffix,
      $timeStep,
      $timeFormat,
      $decimalPlaces
    );
  }

  public static function outputField(
    array  $series,
    string $field,
    string $class,
    string $suffix,
    int    $timeStep,
    string $timeFormat,
    int    $decimalPlaces,
    bool   $isTransfers = false
  ): void {
    $series = array_values(array_filter(
      self::normaliseSeries($series),
      static fn ($point) => isset($point[$field])
    ));

    self::output(
      $series,
      [
        $class => array_map(
          static fn ($point) => (float)($point[$field] ?? 0),
          $series
        )
      ],
      '',
      $suffix,
      $timeStep,
      $timeFormat,
      $decimalPlaces,
      $isTransfers
    );
  }

  public static function outputFields(
    array  $series,
    array  $fields,
    string $suffix,
    int    $timeStep,
    string $timeFormat,
    int    $decimalPlaces,
    bool   $isTransfers = false
  ): void {
    $series = array_values(array_filter(
      self::normaliseSeries($series),
      static fn ($point) => self::hasAnyField($point, array_keys($fields))
    ));
    $lines = [];

    foreach ($fields as $field => $class) {
      $values = array_map(
        static fn ($point) => (float)($point[$field] ?? 0),
        $series
      );

      if (max(array_map('abs', $values ?: [0])) > 0.00001) {
        $lines[$class] = $values;
      }
    }

    self::output(
      $series,
      $lines,
      '',
      $suffix,
      $timeStep,
      $timeFormat,
      $decimalPlaces,
      $isTransfers
    );
  }

  public static function outputSources(
    array  $series,
    string $suffix,
    int    $timeStep,
    string $timeFormat,
    int    $decimalPlaces
  ): void {
    $series = self::normaliseSeries($series);
    $lines = [];

    foreach (self::SOURCES as $source => $class) {
      $values = array_map(
        static fn ($point) => (float)($point['generation'][$source] ?? 0),
        $series
      );

      if (max($values ?: [0]) > 0.00001) {
        $lines[$class] = $values;
      }
    }

    self::output(
      $series,
      $lines,
      '',
      $suffix,
      $timeStep,
      $timeFormat,
      $decimalPlaces
    );
  }

  private static function output(
    array  $series,
    array  $lines,
    string $prefix,
    string $suffix,
    int    $timeStep,
    string $timeFormat,
    int    $decimalPlaces,
    bool   $isTransfers = false
  ): void {
    if (!$series || !$lines) {
      self::outputUnavailable('No US history is available yet');
      return;
    }

    [$minimum, $maximum, $step] = self::getAxis($lines);

    echo '<div class="graph" data-prefix="';
    echo htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8');
    echo '" data-suffix="';
    echo htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8');
    if ($isTransfers) {
      echo '" data-transfers="true';
    }
    echo '">';

    self::outputValueAxis($minimum, $maximum, $step, $prefix, $suffix);
    self::outputTimeAxis($series, $timeStep, $timeFormat);

    echo '<svg viewBox="0 0 ';
    echo self::SIZE;
    echo ' ';
    echo self::SIZE;
    echo '" width="';
    echo self::SIZE;
    echo '" height="';
    echo self::SIZE;
    echo '" preserveAspectRatio="none">';

    self::outputLines($lines, $minimum, $maximum - $minimum);
    self::outputOverlay($series, $lines, $timeFormat, $decimalPlaces);

    echo "</svg></div>\n";
  }

  private static function normaliseSeries(array $series): array {
    $series = array_values(array_filter(
      $series,
      static fn ($point) => isset($point['timestamp'])
    ));

    if (count($series) === 1) {
      $copy = $series[0];
      $copy['timestamp'] = ((int)$copy['timestamp']) + 3600;
      $series[] = $copy;
    }

    return $series;
  }

  private static function hasAnyField(array $point, array $fields): bool {
    foreach ($fields as $field) {
      if (isset($point[$field])) {
        return true;
      }
    }

    return false;
  }

  private static function getAxis(array $lines): array {
    $values = [0];

    foreach ($lines as $line) {
      $values = array_merge($values, $line);
    }

    $minimum = min($values);
    $maximum = max($values);
    $range = max(1, $maximum - $minimum);

    if ($range > 2000) {
      $step = 500;
    } elseif ($range > 1000) {
      $step = 200;
    } elseif ($range > 500) {
      $step = 100;
    } elseif ($range > 200) {
      $step = 50;
    } elseif ($range > 100) {
      $step = 20;
    } elseif ($range > 50) {
      $step = 10;
    } elseif ($range > 20) {
      $step = 5;
    } elseif ($range > 10) {
      $step = 2;
    } else {
      $step = 1;
    }

    return [
      (int)($step * floor($minimum / $step)),
      (int)($step * ceil($maximum / $step)),
      $step
    ];
  }

  private static function outputValueAxis(
    int    $minimum,
    int    $maximum,
    int    $step,
    string $prefix,
    string $suffix
  ): void {
    echo '<div>';

    for ($label = $maximum; $label >= $minimum; $label -= $step) {
      echo '<div>';
      if ($label < 0) {
        echo '&minus;';
      }
      echo $prefix;
      echo number_format(abs($label));
      echo $suffix;
      echo '</div><div></div>';
    }

    echo '</div>';
  }

  private static function outputTimeAxis(
    array  $series,
    int    $step,
    string $format
  ): void {
    $count = count($series);
    $step = min($step, max(1, (int)floor($count / 4)));
    $index = (int)ceil($step / 2);

    echo '<div>';

    foreach ($series as $point) {
      if ($index % $step === 0) {
        echo '<div>';
        echo gmdate($format, (int)$point['timestamp']);
        echo '</div>';
      }

      $index ++;
    }

    echo '</div>';
  }

  private static function outputLines(
    array $lines,
    int   $minimum,
    int   $range
  ): void {
    if ($range === 0) {
      $range = 1;
    }

    foreach ($lines as $class => $values) {
      echo '<polyline class="';
      echo htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
      echo '" points="';
      echo implode(' ', self::getPoints($values, $minimum, $range));
      echo '"/>';
    }
  }

  private static function getPoints(array $values, int $minimum, int $range): array {
    $width = count($values);
    $points = [];

    foreach ($values as $index => $value) {
      $x = round(self::SIZE * ($index + 0.5) / $width);
      $y = round(self::SIZE * (1 - ($value - $minimum) / $range));

      if (count($points) > 1) {
        [$x1, $y1] = $points[count($points) - 2];
        [$x2, $y2] = $points[count($points) - 1];

        if (($y - $y2) * ($x2 - $x1) === ($y2 - $y1) * ($x - $x2)) {
          array_pop($points);
        }
      }

      $points[] = [$x, $y];
    }

    return array_map(
      static fn ($point) => $point[0] . ' ' . $point[1],
      $points
    );
  }

  private static function outputOverlay(
    array  $series,
    array  $lines,
    string $timeFormat,
    int    $decimalPlaces
  ): void {
    $width = count($series);
    $classes = array_keys($lines);

    foreach ($series as $index => $point) {
      echo '<rect x="';
      echo round(self::SIZE * $index / $width);
      echo '" y="0" width="';
      echo round(self::SIZE / $width);
      echo '" height="';
      echo self::SIZE;
      echo '" data-time="';
      echo gmdate($timeFormat, (int)$point['timestamp']);
      echo '" data-values="';
      echo implode(' ', array_map(
        static fn ($class) => number_format(
          (float)$lines[$class][$index],
          $decimalPlaces
        ),
        $classes
      ));
      echo '"/>';
    }
  }
}
