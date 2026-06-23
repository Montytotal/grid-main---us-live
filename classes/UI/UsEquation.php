<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\UsState;

class UsEquation {
  public static function output(UsState $state, ?float $generation = null): void {
    $useSummary = ($generation === null);
    $generation ??= (float)($state->view['summary']['generation'] ?? 0);
    $demand = $useSummary
      ? (float)($state->view['summary']['demand'] ?? $generation)
      : $generation;
    $transfers = $useSummary
      ? ($state->view['summary']['transfers'] ?? null)
      : null;
?>
          <dl class="us-equation" data-operator="+">
            <dt>Demand</dt>
            <dd><?= Value::formatTotalPower($demand) ?><abbr>GW</abbr></dd>
            <dt>Generation</dt>
            <dd><?= Value::formatTotalPower($generation) ?><abbr>GW</abbr></dd>
            <dt>Transfers</dt>
            <dd><?php
              if ($transfers === null) {
                echo '&mdash;';
              } else {
                echo Value::formatTotalPower((float)$transfers);
                echo '<abbr>GW</abbr>';
              }
            ?></dd>
          </dl>
<?php
  }
}
