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
        <section id="about">
          <h2>About the data</h2>
          <p>
            This is a best-effort public view of <?= $sourceLabel ?> electricity data, not a real-time operational dashboard, live grid telemetry, or a complete national accounting of electricity flows.
          </p>
          <p>
            The past-day and past-week views align EIA-930 US48 demand, net generation, and total net interchange hour by hour before averaging the selected period. EIA records positive interchange as an outflow and negative interchange as an inflow; this site reverses that sign so positive cross-border flow means net imports and negative flow means net exports.
          </p>
          <p>
            For each aligned hour, demand is approximately equal to net generation plus the displayed net cross-border flow. Preliminary reports, revisions, missing values, and EIA's aggregation procedures mean the figures may not reconcile exactly.
          </p>
          <p>
            The aligned generation and total-interchange data normally arrive about one day after the reporting hour. Direct interchange with Canada and Mexico is published later and is shown with its own reporting timestamp. Times on this page describe the data, not when the page was refreshed.
          </p>
          <p>
            The EIA-930 data used here cover the Lower 48 states and are supplied by balancing authorities. They do not provide a complete picture of every US electric system, and reported generation can exclude distributed resources, including rooftop solar, or resources that a balancing authority does not directly monitor.
          </p>
          <p>
            The past-year and all-time generation pies and source tables use monthly EIA-923 US generation data. The past year means the latest 12 published months, while all time begins with the available series in 2001. EIA-923 is published several months in arrears. Demand and cross-border operations are not available on this site for those longer ranges; an unavailable value is not an estimate of zero.
          </p>
          <p>
            The country breakdown is limited to reported exchange with Canada and Mexico when available. It does not show interchange between individual US balancing authorities, congestion, outages, or the full physical path electricity takes across the grid, and its different, often older, reporting timestamp means it may not match the newer US48 total exactly.
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
