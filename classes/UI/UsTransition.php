<?php

namespace KateMorley\Grid\UI;

class UsTransition {
  public static function output(): void {
?>
        <section id="transition">
          <h2>
            The energy transition
          </h2>
          <p>
            The United States grid is changing from a system dominated by fossil fuels toward one with more wind, solar, storage, and other low-carbon generation.
          </p>
          <p>
            This prototype currently shows the latest EIA national fuel-mix snapshot, recent fuel-mix trends, and US-wide demand and transfer trends where those feeds publish them. It does not yet include storage charging and discharge, prices, or emissions.
          </p>
          <p>
            As more US data sources are added, this section can track longer-term changes in the national energy mix.
          </p>
        </section>
<?php
  }
}
