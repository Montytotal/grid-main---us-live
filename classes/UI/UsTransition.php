<?php

namespace KateMorley\Grid\UI;

class UsTransition {
  public static function output(): void {
?>
        <section id="transition" class="data-guide">
          <h2>
            Understanding US power grid data
          </h2>
          <h3>What does the latest generation mix show?</h3>
          <p>
            The latest snapshot compares the EIA fuel categories with positive reported generation for that hour. A category such as biomass appears as a separate row only when it has a positive reported value; the &ldquo;Other sources&rdquo; note lists exactly which displayed rows are grouped there. In each time view, Lower 48 EIA-930 demand, net generation and net cross-border flow use aligned observations. Demand is approximately generation plus net cross-border flow.
          </p>
          <h3>How recent is the data?</h3>
          <p>
            The day and week views use recent hourly <a href="https://www.eia.gov/electricity/gridmonitor/">EIA-930 operating data</a>. The aligned national figures normally lag by about one day. Direct-interchange reports for Canada and Mexico normally arrive a day later and carry their own timestamp. This is not live grid telemetry.
          </p>
          <h3>How do the time ranges compare?</h3>
          <p>
            Past day and past week use hourly EIA-930 operations. Past year and all time use daily EIA-930 operations from 2019 for their equation, demand and net-flow graphs. Their generation-mix sections use monthly <a href="https://www.eia.gov/electricity/data/eia923/">EIA-923 generation data</a>, with the all-time mix beginning in 2001, so the two sections have different coverage. Positive cross-border values mean net imports and negative values mean net exports. Read <a href="#about">About the data</a> for coverage and lag details.
          </p>
        </section>
<?php
  }
}
