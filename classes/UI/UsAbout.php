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
            The headline cross-border value is read directly from EIA's US48 total-net-interchange field; this site does not calculate it by subtracting generation from demand. Under EIA's sign convention, demand is normally derived as net generation minus total net interchange&mdash;equivalent here to net generation plus net imports. Preliminary reports, revisions, missing values, and aggregation procedures mean the displayed figures may not reconcile exactly.
          </p>
          <p>
            The aligned generation and total-interchange data normally arrive about one day after the reporting hour. Checked direct-pair interchange with Canada and Mexico normally arrives about two days after operation. When a complete common hour exists, the current headline and country card use it; otherwise the headline falls back to the latest complete national balance and the country rows retain their own timestamp. Times on this page describe the data, not when the page was refreshed.
          </p>
          <p>
            The EIA-930 data used here cover the Lower 48 states and are supplied by balancing authorities. They do not provide a complete picture of every US electric system, and reported generation can exclude distributed resources, including rooftop solar, or resources that a balancing authority does not directly monitor.
          </p>
          <p>
            The past-year and all-time demand, net-generation, and net-flow figures use the EIA-930 daily US48 series, which begins on 1 January 2019. Daily energy is converted to average power, and the site displays how many reporting days are present; missing days are omitted rather than estimated. A fixed-offset Arizona reporting day is used to avoid daylight-saving days of unequal length. This only sets the daily aggregation boundary.
          </p>
          <p>
            The past-year and all-time generation pies, source tables, and generation graphs use monthly EIA-923 US generation data. The past year means the latest 12 published months, while all time begins with the available series in 2001. EIA-923 is published several months in arrears. These generation-mix sections therefore cover different dates from the EIA-930 operational equation, demand graph, and net-flow graph, and should not be read as one perfectly aligned energy balance.
          </p>
          <p>
            Canada and Mexico are the foreign-country aggregates available for the Lower 48 border. Their rows come from EIA direct-interchange reports and are never invented or forced to equal the national figure. For a timestamp-aligned card, the US48 total is EIA&rsquo;s separately reported total net interchange, and the reporting / reconciliation row is simply that total minus the available country rows; it is not an estimate for another country. Different submissions, anomaly handling, revisions, missing reports, and accounting discrepancies can create this difference.
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
