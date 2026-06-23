<?php

namespace KateMorley\Grid\Data;

class EiaFuelMix {
  private const PAGE_LENGTH = 5000;
  private const HISTORY_PAGES = 3;

  /**
   * Maps EIA fuel codes to internal keys for the site.
   */
  private const FUEL_MAP = [
    'COL' => 'coal',
    'NG'  => 'gas',
    'NUC' => 'nuclear',
    'SUN' => 'solar',
    'WND' => 'wind',
    'WAT' => 'hydro',
    'OIL' => 'oil',
    'OTH' => 'other',
    'BIO' => 'biomass',
    'GEO' => 'other'
  ];

  /**
   * Fetches and normalises recent EIA hourly fuel mix rows.
   *
   * @throws DataException If the request or response is invalid
   */
  public static function fetchSample(): array {
    $apiKey = getenv('EIA_API_KEY');

    if ($apiKey === '') {
      throw new DataException('Missing EIA API key');
    }

    $rows = [];

    for ($page = 0; $page < self::HISTORY_PAGES; $page ++) {
      $pageRows = self::fetchPage($apiKey, $page * self::PAGE_LENGTH);
      $rows = array_merge($rows, $pageRows);

      if (count($pageRows) < self::PAGE_LENGTH) {
        break;
      }
    }

    return $rows;
  }

  /**
   * Fetches and normalises one EIA response page.
   *
   * @throws DataException If the request or response is invalid
   */
  private static function fetchPage(string $apiKey, int $offset): array {
    $url = sprintf(
      'https://api.eia.gov/v2/electricity/rto/fuel-type-data/data/?api_key=%s&frequency=hourly&data[0]=value&sort[0][column]=period&sort[0][direction]=desc&offset=%d&length=%d',
      urlencode($apiKey),
      $offset,
      self::PAGE_LENGTH
    );

    $context = stream_context_create([
      'http' => [
        'method' => 'GET',
        'timeout' => 20,
        'header' => implode("\r\n", [
          'User-Agent: Mozilla/5.0',
          'Accept: application/json'
        ])
      ]
    ]);

    $rawData = @file_get_contents($url, false, $context);

    if ($rawData === false) {
      throw new DataException('Failed to read EIA fuel mix data');
    }

    $jsonData = json_decode($rawData, true);

    if (
      !is_array($jsonData) ||
      !isset($jsonData['response']['data']) ||
      !is_array($jsonData['response']['data'])
    ) {
      throw new DataException('Missing EIA response data');
    }

    return array_map(
      fn ($row) => self::normaliseRow($row),
      $jsonData['response']['data']
    );
  }

  /**
   * Normalises one EIA row into a simpler internal structure.
   *
   * @throws DataException If the row is invalid
   */
  private static function normaliseRow(array $row): array {
    if (
      !isset($row['period']) ||
      !isset($row['respondent']) ||
      !isset($row['fueltype']) ||
      !isset($row['value'])
    ) {
      throw new DataException('Missing EIA row fields');
    }

    $fuelType = $row['fueltype'];

    if (!is_string($fuelType)) {
      throw new DataException('Invalid EIA fuel type');
    }

    $key = self::FUEL_MAP[$fuelType] ?? 'other';

    if (!is_numeric($row['value'])) {
      throw new DataException('Invalid EIA value');
    }

    return [
      'time'       => $row['period'],
      'respondent' => $row['respondent'],
      'fueltype'   => $fuelType,
      'key'        => $key,
      'value'      => (float)$row['value']
    ];
  }
}
