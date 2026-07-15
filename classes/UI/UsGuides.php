<?php

namespace KateMorley\Grid\UI;

class UsGuides {
  public static function output(string $assetPath): void {
    $path = htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8');
?>
      <section id="explore" class="data-directory">
        <p class="section-kicker">Data guides</p>
        <h2>Explore U.S. electricity data</h2>
        <p>
          Read how each national series is measured, aligned and limited before comparing the live dashboard and historical views.
        </p>
        <div class="data-directory-links">
          <a href="<?= $path ?>us-electricity-generation/">
            <strong>U.S. electricity generation by source</strong>
            <span>Understand fuel mix, units, time ranges and source coverage.</span>
          </a>
          <a href="<?= $path ?>us-electricity-demand/">
            <strong>U.S. electricity demand data</strong>
            <span>Learn how the Lower 48 demand series and national balance work.</span>
          </a>
          <a href="<?= $path ?>us-cross-border-electricity-flow/">
            <strong>U.S. electricity imports and exports</strong>
            <span>Read the flow sign, Canada and Mexico reports, and reconciliation limits.</span>
          </a>
          <a href="<?= $path ?>methodology/">
            <strong>Methodology and data sources</strong>
            <span>Review EIA datasets, conversions, alignment and missing-data rules.</span>
          </a>
        </div>
      </section>
<?php
  }
}
