<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\UsState;

class UsEquation {
  public static function output(UsState $state, ?array $equation = null): void {
    $equation ??= is_array($state->view['equation'] ?? null)
      ? $state->view['equation']
      : [];

    $demand = self::value($equation, 'demand');
    $generation = self::value($equation, 'generation');
    $netImports = self::value($equation, 'net_imports');
    $available = $demand !== null
      && $generation !== null
      && $netImports !== null;
    $isExport = $available && $netImports < 0;
    $flowLabel = !$available || $netImports === 0.0
      ? 'Net flow'
      : ($isExport ? 'Net exports' : 'Net imports');
?>
          <dl
            class="us-equation<?= $available ? '' : ' is-unavailable' ?>"
            data-equals="<?= $available ? '&asymp;' : '' ?>"
            data-operator="<?= $available ? ($isExport ? '&minus;' : '+') : '' ?>"
          >
            <dt>Demand</dt>
            <dd><?= self::formatPower($demand) ?></dd>
            <dt>Net generation</dt>
            <dd><?= self::formatPower($generation) ?></dd>
            <dt><?= $flowLabel ?></dt>
            <dd><?= self::formatPower($netImports, true) ?></dd>
          </dl>
<?php
  }

  private static function value(array $equation, string $field): ?float {
    return isset($equation[$field]) && is_numeric($equation[$field])
      ? (float)$equation[$field]
      : null;
  }

  private static function formatPower(?float $value, bool $absolute = false): string {
    if ($value === null) {
      return '&mdash;';
    }

    return Value::formatTotalPower($absolute ? abs($value) : $value)
      . '<abbr>GW</abbr>';
  }
}
