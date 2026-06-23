<?php

namespace KateMorley\Grid\Data;

class UsStateMap {
  /**
   * Converts the latest US datum into the raw-key map style expected by the
   * existing State classes.
   */
  public static function build(array $datum): array {
    $generation = $datum['generation'];

    return [
      // UK-style generation keys reused for compatibility with State classes
      'coal'           => $generation['coal'] ?? 0,
      'ccgt'           => $generation['gas'] ?? 0,
      'ocgt'           => 0,
      'nuclear'        => $generation['nuclear'] ?? 0,
      'oil'            => $generation['oil'] ?? 0,
      'wind'           => $generation['wind'] ?? 0,
      'hydro'          => $generation['hydro'] ?? 0,
      'pumped'         => 0,
      'biomass'        => $generation['biomass'] ?? 0,
      'battery'        => 0,
      'other'          => $generation['other'] ?? 0,

      // Reuse embedded keys to fit current State\Generation logic
      'embedded_solar' => $generation['solar'] ?? 0,
      'embedded_wind'  => 0,

      // Keep transfer/interconnector keys present so existing classes don't break
      'ifa'            => 0,
      'moyle'          => 0,
      'britned'        => 0,
      'ewic'           => 0,
      'nemo'           => 0,
      'ifa2'           => 0,
      'nsl'            => 0,
      'eleclink'       => 0,
      'viking'         => 0,
      'greenlink'      => 0,

      // Placeholder values for other parts of the UI
      'price'          => 0,
      'emissions'      => 0,
      'visits'         => 0
    ];
  }
}