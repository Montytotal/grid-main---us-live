<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\UsState;

class UsUI {
  public static function output(
    UsState $state,
    string $assetPath = '../'
  ): void {
    $sampleTime = (int)($state->view['equation']['timestamp'] ?? 0);
    $timeLabel = $sampleTime > 0 ? 'Balance time' : 'Fuel-mix time';
    $sourceLabel = htmlspecialchars(
      (string)($state->latest['source']['label'] ?? 'EIA'),
      ENT_QUOTES,
      'UTF-8'
    );

    if ($sampleTime <= 0) {
      $sampleTime = strtotime((string)($state->latest['time'] ?? ''));

      if ($sampleTime === false) {
        $sampleTime = time();
      }
    }

    $isCanonicalHome = $assetPath === './';

?>
<!DOCTYPE html>
<html lang="en-us">
  <head>
    <title>USPowerData | U.S. Electricity Grid, Generation &amp; Demand Data</title>
    <meta
      name="description"
      content="USPowerData presents regularly updated U.S. Lower 48 electricity generation, demand, fuel mix and net cross-border flow charts using <?= $sourceLabel ?> data."
    >
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="canonical" href="https://uspowerdata.com/">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_US">
    <meta property="og:site_name" content="USPowerData">
    <meta property="og:title" content="U.S. Electricity Grid, Generation &amp; Demand Data | USPowerData">
    <meta
      property="og:description"
      content="Explore regularly updated U.S. Lower 48 electricity generation, demand, fuel mix and net cross-border flow charts using EIA data."
    >
    <meta property="og:url" content="https://uspowerdata.com/">
<?php if ($isCanonicalHome) { ?>
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "USPowerData",
        "alternateName": "US Power Data",
        "url": "https://uspowerdata.com/",
        "description": "U.S. Lower 48 electricity data covering reported generation by source, demand, fuel mix, net cross-border flow and historical trends.",
        "inLanguage": "en-US"
      }
    </script>
<?php } ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Proza+Libre:wght@300;400&display=swap"
    >
    <link
      rel="stylesheet"
      href="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>grid.css?<?= filemtime(__DIR__ . '/../../public/grid.css') ?>"
      type="text/css"
    >
    <link rel="icon" href="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>favicon.png" type="image/png">
    <link rel="icon" href="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>favicon.svg?<?= floor(time() / 300) ?>" type="image/svg+xml">
    <script
      src="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>grid.js?<?= filemtime(__DIR__ . '/../../public/grid.js') ?>"
      defer
    ></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}

      gtag('consent', 'default', {
        'analytics_storage': 'granted'
      });

      gtag('consent', 'default', {
        'ad_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'analytics_storage': 'denied',
        'wait_for_update': 500,
        'region': [
          'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
          'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
          'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'IS', 'LI', 'NO',
          'GB', 'CH'
        ]
      });
    </script>
<?php UsAds::outputHeadScript(); ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-DDT6043DWS"></script>
    <script>
      gtag('js', new Date());
      gtag('config', 'G-DDT6043DWS');
    </script>
  </head>
  <body class="us-grid">
    <header aria-label="Site">
      <nav>
        <a
          class="us-brand"
          href="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>"
          aria-label="USPowerData home"
        >
          <img
            src="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>uspowerdata-logo.png?<?= filemtime(__DIR__ . '/../../public/uspowerdata-logo.png') ?>"
            width="1774"
            height="887"
            alt="USPowerData"
          >
        </a>
        <div>
          <a href="#latest">Live</a>
          <a href="#history">Data</a>
        </div>
      </nav>
    </header>
    <div class="us-site-layout">
      <main>
      <h1>U.S. Electricity Grid and Power Data</h1>
      <p>
        USPowerData provides accessible national electricity grid data for the U.S. Lower 48, including reported generation by source, demand, fuel mix and net cross-border flow. Explore regularly updated charts, tables and historical comparisons built from EIA datasets; most operational figures arrive with at least a one-day delay.
      </p>

      <div id="status" class="columns">
        <section>
<?php UsStatus::output($state, UsStatus::time($sampleTime), $timeLabel); ?>
        </section>
        <section>
<?php UsEquation::output($state); ?>
        </section>
      </div>

<?php UsAds::outputSlot('top'); ?>
<?php UsLatestSection::output($state); ?>
<?php UsTabs::output($state); ?>
<?php UsGuides::output($assetPath); ?>
<?php UsAds::outputSlot('mid'); ?>
      <div class="columns">
<?php UsTransition::output(); ?>
<?php UsAbout::output($state); ?>
      </div>

      </main>

      <aside class="us-ad-rail us-ad-rail-left" aria-label="Advertisement">
        <span aria-hidden="true">Advertisement</span>
<?php UsAds::outputSlot('left'); ?>
      </aside>

      <aside class="us-ad-rail us-ad-rail-right" aria-label="Advertisement">
        <span aria-hidden="true">Advertisement</span>
<?php UsAds::outputSlot('right'); ?>
      </aside>
    </div>

    <footer id="us-footer">
      <div>
        <p>&copy; 2026 USPowerData. Independent website using third-party energy data.</p>
        <nav aria-label="Footer">
          <a href="#latest">Latest generation</a>
          <a href="#history">Historical trends</a>
          <a href="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>about/">About USPowerData</a>
          <a href="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>methodology/">Methodology</a>
          <a href="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>contact/">Contact</a>
          <a href="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>terms/">Terms</a>
          <a href="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>privacy/">Privacy &amp; cookies</a>
        </nav>
      </div>
    </footer>

    <dialog>
      <h2>Chart details</h2>
      <form method="dialog"><button aria-label="Close chart details"><svg viewBox="0 0 30 30" aria-hidden="true"><path d="M6,6 24,24"/><path d="M6,24 24,6"/></svg></button></form>
      <div></div>
    </dialog>
  </body>
</html>
<?php
  }
}
