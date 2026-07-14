<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\UsState;

class UsStatus {
  public static function output(
    UsState $state,
    string  $time,
    string  $timeLabel = 'Time'
  ): void {
?>
          <dl>
            <dt><?= htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') ?></dt>
            <dd><?= $time ?></dd>
            <dt>Price</dt>
            <dd>&mdash;<abbr>/MWh</abbr></dd>
            <dt>Emissions</dt>
            <dd>&mdash;<abbr>g/kWh</abbr></dd>
          </dl>
<?php
  }

  public static function time(int $time): string {
    return (
      '<time datetime="'
      . gmdate('Y-m-d\TH:i:s\Z', $time)
      . '">'
      . gmdate('j M Y, ', $time)
      . gmdate('g:i', $time)
      . '<abbr>'
      . gmdate('a', $time)
      . ' UTC'
      . '</abbr></time>'
    );
  }
}
