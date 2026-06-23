<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\State;

/** Outputs the disclaimer section. */
class About {
  /**
   * Outputs the disclaimer section.
   *
   * @param State $state The state
   */
  public static function output(State $state): void {
?>
        <section>
          <h2>Data sources and disclaimer</h2>
          <p>
            Contains BMRS data © Elexon Limited copyright and database right <?= date('Y') ?>.
          </p>
          <p>
            Supported by National Energy SO Open Data.
          </p>
          <p>
            This website uses live and historical energy data from Elexon BMRS and NESO.
          </p>
          <p>
            This is an independent project and is not affiliated with, endorsed by, or officially connected with Elexon Limited, NESO, or any other data provider.
          </p>
          <p>
            Data is provided for general information purposes only and may be delayed, incomplete, inaccurate, or subject to revision. No warranty is given as to its accuracy, completeness, or fitness for any particular purpose.
          </p>
          <p>
            Historical price data is not present in the uploaded CSV, and historical country-by-country interconnector splits are not present there either.
          </p>
        </section>
<?php
  }
}