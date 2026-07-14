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
            The latest snapshot compares reported US electricity generation from coal, gas, oil, nuclear, wind, solar, hydroelectricity, biomass and other sources. Demand and cross-border transfer figures come from separate reports, so they may cover different hours or balancing authorities and may not reconcile exactly.
          </p>
          <h3>How recent is the data?</h3>
          <p>
            The day and week views use hourly <a href="https://www.eia.gov/electricity/gridmonitor/">EIA-930 operating data</a> for the Lower 48 states. Reports usually arrive at least one day after the hour shown, so “latest” means the newest published data rather than a real-time physical view of the grid.
          </p>
          <h3>How do the time ranges compare?</h3>
          <p>
            Past day and past week show recent hourly trends. Past year and all time use monthly <a href="https://www.eia.gov/electricity/data/eia923/">EIA-923 generation data</a>; the all-time series begins in 2001. Select a range above to update its generation mix and charts, and read <a href="#about">About the data</a> for coverage and lag details.
          </p>
        </section>
<?php
  }
}
