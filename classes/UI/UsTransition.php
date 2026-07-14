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
            The latest snapshot compares reported generation from coal, gas, oil, nuclear, wind, solar, hydroelectricity, biomass and other sources. In the day and week views, Lower 48 EIA-930 demand, net generation and net cross-border flow are aligned hour by hour and then averaged over the selected period. Demand is approximately generation plus net cross-border flow.
          </p>
          <h3>How recent is the data?</h3>
          <p>
            The day and week views use recent hourly <a href="https://www.eia.gov/electricity/gridmonitor/">EIA-930 operating data</a>. The aligned national figures normally lag by about one day. Canada and Mexico data arrive later and carry their own timestamp. This is not live grid telemetry.
          </p>
          <h3>How do the time ranges compare?</h3>
          <p>
            Past day and past week show recent EIA-930 operations. Past year and all time retain monthly <a href="https://www.eia.gov/electricity/data/eia923/">EIA-923 generation data</a>, while demand and cross-border operations are unavailable for those ranges; the all-time generation series begins in 2001. Positive cross-border values mean net imports and negative values mean net exports. Read <a href="#about">About the data</a> for coverage and lag details.
          </p>
        </section>
<?php
  }
}
