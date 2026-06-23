<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\UsState;

class UsAbout {
  public static function output(UsState $state): void {
    $sourceLabel = htmlspecialchars(
      (string)($state->latest['source']['label'] ?? 'EIA'),
      ENT_QUOTES,
      'UTF-8'
    );
?>
        <section>
          <h2>About this site</h2>
          <p>
            This page currently uses <?= $sourceLabel ?> fuel-mix data for the US generation snapshot.
          </p>
          <p>
            Regional pages should use direct ISO, RTO, or BPA data where an official source exists, with EIA-930 balancing-authority data as the fallback where there is no direct source.
          </p>
          <p>
            The current US view is built from source-level fuel data and a project-specific aggregation layer.
          </p>
          <p>
            Negative generation values are clamped to zero before display and aggregation because they can appear as accounting artefacts in source feeds.
          </p>
          <p>
            It should be treated as a live prototype snapshot rather than a final authoritative national accounting model.
          </p>
          <p>
            Demand and transfers use the EIA regional operating feed when that feed is available for the same hour. Storage, prices, and emissions are not modelled yet.
          </p>
          <p>
            Values may change as the EIA revises data, updates respondent coverage, or categorises fuel types differently over time.
          </p>
          <p>
            This is an independent project and is not affiliated with, endorsed by, or officially connected with the EIA or any grid operator.
          </p>
        </section>
<?php
  }
}
