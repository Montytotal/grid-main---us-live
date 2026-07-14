<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\UsState;

class UsUI {
  public static function output(
    UsState $state,
    string $assetPath = '../'
  ): void {
    $sampleTime = strtotime((string)($state->latest['time'] ?? ''));
    $sourceLabel = htmlspecialchars(
      (string)($state->latest['source']['label'] ?? 'EIA'),
      ENT_QUOTES,
      'UTF-8'
    );

    if ($sampleTime === false) {
      $sampleTime = time();
    }

?>
<!DOCTYPE html>
<html lang="en-us">
  <head>
    <title>US Electricity System: Live</title>
    <meta
      name="description"
      content="Live electricity generation snapshot for the United States using <?= $sourceLabel ?> fuel-mix data"
    >
    <meta name="viewport" content="width=device-width,initial-scale=1">
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
        <a href="./" aria-label="US Electricity System: Live">
          <svg viewBox="0 0 160 160" role="img" aria-labelledby="us-grid-logo-title">
            <title id="us-grid-logo-title">US Electricity System</title>
            <path d="M80 8 22 42v76l58 34 58-34V42zM40 52l40-24 40 24v56l-40 24-40-24z"/>
            <path d="M54 86h52v16H54zm0-28h52v16H54z"/>
          </svg>
        </a>
        <div>
          <a href="#latest">Live</a>
          <a href="#transition">Data</a>
        </div>
      </nav>
    </header>
    <div class="us-site-layout">
      <main>
      <h1>US Electricity System: Live</h1>
      <p>
        The US electricity system is made up of regional power grids and balancing authorities that supply electricity across the country.
      </p>

      <div id="status" class="columns">
        <section>
<?php UsStatus::output($state, UsStatus::time($sampleTime), true); ?>
        </section>
        <section>
<?php UsEquation::output($state); ?>
        </section>
      </div>

<?php UsAds::outputSlot('top'); ?>
<?php UsLatestSection::output($state); ?>
<?php UsTabs::output($state); ?>
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
        Independent website using third-party energy data.
        <a href="<?= htmlspecialchars($assetPath, ENT_QUOTES, 'UTF-8') ?>privacy/">Privacy &amp; cookies</a>
      </div>
    </footer>

    <dialog>
      <h2></h2>
      <form method="dialog"><button><svg viewBox="0 0 30 30"><path d="M6,6 24,24"/><path d="M6,24 24,6"/></svg></button></form>
      <div></div>
    </dialog>
  </body>
</html>
<?php
  }
}
