<?php

namespace KateMorley\Grid\Data;

class EiaInterchangeData {
  private const PAGE_LENGTH = 5000;
  private const NEIGHBOURS = [
    'CAN' => 'canada',
    'MEX' => 'mexico',
  ];

  /**
   * Fetches recent EIA border interchange rows for Canada and Mexico.
   *
   * Positive returned values mean net imports into the US.
   *
   * @throws DataException If the request or response is invalid
   */
  public static function fetchSample(): array {
    $apiKey = getenv('EIA_API_KEY');

    if ($apiKey === '') {
      throw new DataException('Missing EIA API key');
    }

    $url = sprintf(
      'https://api.eia.gov/v2/electricity/rto/interchange-data/data/?api_key=%s&frequency=hourly&data[0]=value',
      urlencode($apiKey)
    );

    foreach (array_keys(self::NEIGHBOURS) as $neighbour) {
      $url .= '&facets[toba][]=' . urlencode($neighbour);
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
      throw new DataException('Failed to read EIA interchange data');
    }

    $jsonData = json_decode($rawData, true);

    if (
      !is_array($jsonData) ||
      !isset($jsonData['response']['data']) ||
      !is_array($jsonData['response']['data'])
    ) {
      throw new DataException('Missing EIA interchange response data');
    }

    $rows = [];

    foreach ($jsonData['response']['data'] as $row) {
      $normalised = self::normaliseRow($row);

      if ($normalised !== null) {
        $rows[] = $normalised;
      }
    }

    return $rows;
  }

  private static function normaliseRow(array $row): ?array {
    if (
      !isset($row['period']) ||
      !isset($row['fromba']) ||
      !isset($row['toba']) ||
      !isset($row['value'])
    ) {
      throw new DataException('Missing EIA interchange row fields');
    }

    if ((string)$row['fromba'] === 'US48') {
      return null;
    }

    $neighbour = self::NEIGHBOURS[(string)$row['toba']] ?? null;

    if ($neighbour === null) {
      return null;
    }

    if (!is_numeric($row['value'])) {
      throw new DataException('Invalid EIA interchange value');
    }

    return [
      'time' => (string)$row['period'],
      'neighbour' => $neighbour,
      'value' => UsNetFlow::importsPositive((float)$row['value'])
    ];
  }
}
