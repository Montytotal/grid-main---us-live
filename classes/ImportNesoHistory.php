<?php

declare(strict_types=1);

use KateMorley\Grid\Environment;

spl_autoload_register(function ($class) {
  require_once(
    __DIR__
    . '/'
    . strtr(substr($class, 16), '\\', '/')
    . '.php'
  );
});

require_once __DIR__ . '/Environment.php';

Environment::load(dirname(__DIR__) . '/.env');

set_time_limit(0);

$csvPath = __DIR__ . '/df_fuel_ckan.csv';

if (!file_exists($csvPath)) {
  fwrite(STDERR, "CSV not found at {$csvPath}\n");
  exit(1);
}

$connection = new mysqli(
  getenv('DATABASE_HOSTNAME'),
  getenv('DATABASE_USERNAME'),
  getenv('DATABASE_PASSWORD'),
  getenv('DATABASE_DATABASE')
);

if ($connection->connect_error) {
  fwrite(STDERR, "Database connection failed: {$connection->connect_error}\n");
  exit(1);
}

$connection->set_charset('utf8mb4');

$columns = [
  'embedded_wind', 'embedded_solar', 'coal', 'ccgt', 'ocgt', 'nuclear',
  'oil', 'wind', 'hydro', 'pumped', 'biomass', 'battery', 'other',
  'ifa', 'moyle', 'britned', 'ewic', 'nemo', 'ifa2', 'nsl', 'eleclink',
  'viking', 'greenlink', 'price', 'emissions', 'visits'
];

function f2(float $value): string {
  return number_format($value, 2, '.', '');
}

function insertBatch(mysqli $connection, array $rows, array $columns): void {
  if ($rows === []) {
    return;
  }

  $valueSql = implode(",\n", array_map(
    fn(array $row) => '(' . implode(',', $row) . ')',
    $rows
  ));

  $updateSql = implode(',', array_map(
    fn(string $column) => "{$column}=VALUES({$column})",
    $columns
  ));

  $sql = 'INSERT INTO past_half_hours (`time`,' . implode(',', $columns) . ') VALUES
'
       . $valueSql
       . ' ON DUPLICATE KEY UPDATE '
       . $updateSql;

  if (!$connection->query($sql)) {
    throw new RuntimeException($connection->error);
  }
}

function aggregateTable(mysqli $connection, string $source, string $destination, string $timeExpression, array $columns): void {
  $avgSql = implode(',', array_map(
    fn(string $column) => "AVG({$column}) AS {$column}",
    $columns
  ));

  $updateSql = implode(',', array_map(
    fn(string $column) => "{$column}=VALUES({$column})",
    $columns
  ));

  $sql = 'INSERT INTO ' . $destination . ' (`time`,' . implode(',', $columns) . ') '
       . 'SELECT ' . $timeExpression . ' AS aggregated_time,' . $avgSql . ' '
       . 'FROM ' . $source . ' GROUP BY aggregated_time '
       . 'ON DUPLICATE KEY UPDATE ' . $updateSql;

  if (!$connection->query($sql)) {
    throw new RuntimeException($connection->error);
  }
}

echo "Resetting historical tables...\n";
$connection->query('TRUNCATE TABLE past_half_hours');
$connection->query('TRUNCATE TABLE past_days');
$connection->query('TRUNCATE TABLE past_weeks');
$connection->query('TRUNCATE TABLE past_years');
$connection->query('TRUNCATE TABLE wind_records');

$handle = fopen($csvPath, 'r');
$header = fgetcsv($handle);

if ($header === false) {
  fwrite(STDERR, "Failed to read CSV header\n");
  exit(1);
}

$index = array_flip($header);
$required = [
  'DATETIME', 'GAS', 'COAL', 'NUCLEAR', 'WIND', 'WIND_EMB',
  'HYDRO', 'IMPORTS', 'BIOMASS', 'OTHER', 'SOLAR', 'STORAGE',
  'CARBON_INTENSITY'
];

foreach ($required as $column) {
  if (!isset($index[$column])) {
    fwrite(STDERR, "Missing required column: {$column}\n");
    exit(1);
  }
}

$rows = [];
$count = 0;

while (($data = fgetcsv($handle)) !== false) {
  $time = date('Y-m-d H:i:s', strtotime($data[$index['DATETIME']] . ' UTC'));

  $embeddedWind  = (float) $data[$index['WIND_EMB']] / 1000;
  $embeddedSolar = (float) $data[$index['SOLAR']] / 1000;
  $coal          = (float) $data[$index['COAL']] / 1000;
  $ccgt          = (float) $data[$index['GAS']] / 1000;
  $ocgt          = 0.0;
  $nuclear       = (float) $data[$index['NUCLEAR']] / 1000;
  $oil           = 0.0;
  $wind          = (float) $data[$index['WIND']] / 1000;
  $hydro         = (float) $data[$index['HYDRO']] / 1000;

  // Total storage only, not pumped vs battery
  $pumped        = (float) $data[$index['STORAGE']] / 1000;
  $biomass       = (float) $data[$index['BIOMASS']] / 1000;
  $battery       = 0.0;
  $other         = (float) $data[$index['OTHER']] / 1000;

  // Total imports only, not country split
  $ifa           = (float) $data[$index['IMPORTS']] / 1000;
  $moyle         = 0.0;
  $britned       = 0.0;
  $ewic          = 0.0;
  $nemo          = 0.0;
  $ifa2          = 0.0;
  $nsl           = 0.0;
  $eleclink      = 0.0;
  $viking        = 0.0;
  $greenlink     = 0.0;

  // No historical wholesale price in this CSV
  $price         = 0.0;
  $emissions     = (int) round((float) $data[$index['CARBON_INTENSITY']]);
  $visits        = 0;

  $rows[] = [
    '"' . $connection->real_escape_string($time) . '"',
    f2($embeddedWind),
    f2($embeddedSolar),
    f2($coal),
    f2($ccgt),
    f2($ocgt),
    f2($nuclear),
    f2($oil),
    f2($wind),
    f2($hydro),
    f2($pumped),
    f2($biomass),
    f2($battery),
    f2($other),
    f2($ifa),
    f2($moyle),
    f2($britned),
    f2($ewic),
    f2($nemo),
    f2($ifa2),
    f2($nsl),
    f2($eleclink),
    f2($viking),
    f2($greenlink),
    f2($price),
    (string) $emissions,
    (string) $visits
  ];

  if (count($rows) >= 1000) {
    insertBatch($connection, $rows, $columns);
    $count += count($rows);
    echo "Imported {$count} half-hour rows...\n";
    $rows = [];
  }
}

fclose($handle);

if ($rows !== []) {
  insertBatch($connection, $rows, $columns);
  $count += count($rows);
}

echo "Imported {$count} half-hour rows.\n";

echo "Aggregating days, weeks and years...\n";

aggregateTable(
  $connection,
  'past_half_hours',
  'past_days',
  'DATE_SUB(DATE_SUB(time, INTERVAL MINUTE(time) MINUTE), INTERVAL HOUR(time) HOUR)',
  $columns
);

aggregateTable(
  $connection,
  'past_days',
  'past_weeks',
  'DATE_SUB(time, INTERVAL WEEKDAY(time) DAY)',
  $columns
);

aggregateTable(
  $connection,
  'past_days',
  'past_years',
  'DATE_SUB(DATE_SUB(time, INTERVAL (DAYOFMONTH(time) - 1) DAY), INTERVAL (MONTH(time) - 1) MONTH)',
  $columns
);

echo "Rebuilding wind records...\n";
$maxRecord = 0.0;
$result = $connection->query('SELECT time, embedded_wind + wind AS value FROM past_half_hours ORDER BY time ASC');

while ($row = $result->fetch_assoc()) {
  $value = (float) $row['value'];
  $roundedValue = f2($value);

  if ($value > $maxRecord) {
    $maxRecord = $value;

    $connection->query(
      'INSERT IGNORE INTO wind_records (value, time) VALUES ('
      . $roundedValue
      . ', "'
      . $connection->real_escape_string($row['time'])
      . '")'
    );
  }
}

echo "Done.\n";
echo "Generation and emissions history have been backfilled.\n";
echo "Historical price remains 0 because the CSV does not contain price history.\n";
echo "Historical imports are stored as a total-import proxy, not a true country-by-country split.\n";