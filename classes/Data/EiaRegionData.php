<?php

namespace KateMorley\Grid\Data;

class EiaRegionData {
  private const PAGE_LENGTH = 5000;
  private const RESPONDENT = 'US48';
  private const TYPES = ['D', 'NG', 'TI'];

  /**
   * Fetches recent US-wide EIA hourly operating rows.
   *
   * @throws DataException If the request or response is invalid
   */
  public static function fetchSample(): array {
    $apiKey = getenv('EIA_API_KEY');

    if ($apiKey === '') {
      throw new DataException('Missing EIA API key');
    }

    $url = sprintf(
      'https://api.eia.gov/v2/electricity/rto/region-data/data/?api_key=%s&frequency=hourly&data[0]=value&facets[respondent][]=%s',
      urlencode($apiKey),
      urlencode(self::RESPONDENT)
    );

    foreach (self::TYPES as $type) {
      $url .= '&facets[type][]=' . urlencode($type);
    }

    $url .= sprintf(
      '&sort[0][column]=period&sort[0][direction]=desc&offset=0&length=%d',
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
      throw new DataException('Failed to read EIA regional operating data');
    }

    $jsonData = json_decode($rawData, true);

    if (
      !is_array($jsonData) ||
      !isset($jsonData['response']['data']) ||
      !is_array($jsonData['response']['data'])
    ) {
      throw new DataException('Missing EIA regional operating response data');
    }

    return array_map(
      fn ($row) => self::normaliseRow($row),
      $jsonData['response']['data']
    );
  }

  /**
   * Normalises one EIA regional row.
   *
   * @throws DataException If the row is invalid
   */
  private static function normaliseRow(array $row): array {
    if (
      !isset($row['period']) ||
      !isset($row['type']) ||
      !isset($row['value'])
    ) {
      throw new DataException('Missing EIA regional row fields');
    }

    if (!is_numeric($row['value'])) {
      throw new DataException('Invalid EIA regional value');
    }

    return [
      'time'  => (string)$row['period'],
      'type'  => (string)$row['type'],
      'value' => (float)$row['value']
    ];
  }
}
