<?php

// Updates the site

use KateMorley\Grid\Database;
use KateMorley\Grid\Environment;
use KateMorley\Grid\Data\DataException;
use KateMorley\Grid\Data\Demand;
use KateMorley\Grid\Data\Emissions;
use KateMorley\Grid\Data\Generation;
use KateMorley\Grid\Data\Pricing;
use KateMorley\Grid\Data\Visits;
use KateMorley\Grid\State\UsState;
use KateMorley\Grid\UI\Favicon;
use KateMorley\Grid\UI\UsAds;
use KateMorley\Grid\UI\UsUI;

spl_autoload_register(function ($class) {
  require_once(
    __DIR__
    . '/classes/'
    . strtr(substr($class, 16), '\\', '/')
    . '.php'
  );
});

Environment::load(__DIR__ . '/.env');

$database = new Database();

foreach ([
  'Updating generation… ' => function ($database) {
    Generation::update($database);
  },

  'Updating emissions…  ' => function ($database) {
    Emissions::update($database);
  },

  'Updating pricing…    ' => function ($database) {
    Pricing::update($database);
  },

  'Updating demand…     ' => function ($database) {
    Demand::update($database);
  },

  'Updating visits…     ' => function ($database) {
    Visits::update($database);
  },

  'Finishing update…    ' => function ($database) {
    $database->finishUpdate();
  },

  'Outputting files…    ' => function ($database) {
    $state = $database->getState();

    file_put_contents(
      __DIR__ . '/public/favicon.svg',
      Favicon::create($state->latest->types),
      LOCK_EX
    );

    $usState = UsState::build();

    ob_start();
    UsUI::output($usState, './');
    file_put_contents(__DIR__ . '/public/index.html', ob_get_clean(), LOCK_EX);

    $outputDir = __DIR__ . '/public/us';

    if (!is_dir($outputDir)) {
      mkdir($outputDir, 0755, true);
    }

    ob_start();
    UsUI::output($usState);
    file_put_contents($outputDir . '/index.html', ob_get_clean(), LOCK_EX);

    $adsTxt = UsAds::adsTxt();

    if ($adsTxt !== null) {
      file_put_contents(__DIR__ . '/public/ads.txt', $adsTxt, LOCK_EX);
    }
  }
] as $action => $callback) {
  echo $action;

  $start = microtime(true);

  try {
    $callback($database);

    echo 'OK';

    $database->clearErrors($action);
  } catch (DataException $e) {
    $error = $e->getMessage();
    echo 'ERROR: ' . $error;

    if (
      $database->getErrorCount($action, $error)
      >= (int)getenv('ERROR_REPORTING_THRESHOLD')
    ) {
      $database->clearErrors($action);

      if ((int)getenv('ERROR_REPORTING_THRESHOLD') > 0) {
        trigger_error(trim($action) . ' ' . $error);
      }
    }
  }

  echo ' (' . sprintf('%0.3f', microtime(true) - $start) . " seconds)\n";
}
