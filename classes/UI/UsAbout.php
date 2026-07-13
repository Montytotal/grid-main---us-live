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
            This is a best-effort public view of <?= $sourceLabel ?> hourly grid data, not a real-time operational dashboard or a complete national accounting of electricity flows.
          </p>
          <p>
            The generation and total-interchange data normally arrive at least one day after the reporting hour. Direct interchange data can arrive later, and publication delays can be longer. The time displayed on the page is the data's reporting hour, not the time it was published or refreshed.
          </p>
          <p>
            The EIA-930 data used here cover the Lower 48 states and are supplied by balancing authorities. They do not provide a complete picture of every US electric system, and reported generation can exclude distributed resources, including rooftop solar, or resources that a balancing authority does not directly monitor.
          </p>
          <p>
            Generation, demand, and interchange are reported separately and can have different coverage, revisions, and reporting times. The figures may therefore not reconcile exactly, and the demand-minus-generation value must not be read as a measured real-time transfer.
          </p>
          <p>
            The transfers shown are limited to reported cross-border exchange with Canada and Mexico when available. This site does not show live transmission flows, interchange between individual balancing authorities, congestion, outages, or the full physical path electricity takes across the grid.
          </p>
          <p>
            Fuel types are grouped for display. Geothermal and any unmapped fuel types are included in <em>Other</em>; percentages are shares of the generation displayed here, not necessarily shares of all electricity produced or consumed. Negative generation values are set to zero before display because they can occur as source-data accounting artefacts.
          </p>
          <p>
            Storage charging and discharge, wholesale prices, and carbon-intensity or emissions figures are not currently modelled. Missing values and unavailable series are not estimates of zero.
          </p>
          <p>
            Values may change as the EIA revises data, corrects reporting issues, updates respondent coverage, or categorises fuel types differently over time. Use official grid-operator and EIA data for operational, safety-critical, or market decisions.
          </p>
          <p>
            This is an independent project and is not affiliated with, endorsed by, or officially connected with the EIA or any grid operator.
          </p>
        </section>
<?php
  }
}
