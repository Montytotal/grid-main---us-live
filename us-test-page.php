<?php

use KateMorley\Grid\Environment;
use KateMorley\Grid\State\UsState;
use KateMorley\Grid\UI\UsUI;

spl_autoload_register(function ($class) {
  $prefix = 'KateMorley\\Grid\\';

  if (strpos($class, $prefix) !== 0) {
    return;
  }

  $path =
    __DIR__
    . '/classes/'
    . strtr(substr($class, strlen($prefix)), '\\', '/')
    . '.php';

  if (!file_exists($path)) {
    throw new RuntimeException(
      'Autoload file not found for ' . $class . ': ' . $path
    );
  }

  require_once($path);
});

Environment::load(__DIR__ . '/.env');

$state = UsState::build();

$outputDir = __DIR__ . '/public/us';

if (!is_dir($outputDir)) {
  mkdir($outputDir, 0755, true);
}

ob_start();
UsUI::output($state);
file_put_contents($outputDir . '/index.html', ob_get_clean(), LOCK_EX);

echo "Wrote public/us/index.html\n";