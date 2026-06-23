<?php

namespace KateMorley\Grid\UI;

use KateMorley\Grid\State\State;

/** Outputs the user interface. */
class UI {
  /**
   * Outputs the user interface.
   *
   * @param State $state The state
   */
  public static function output(State $state): void {
?>
<!DOCTYPE html>
<html lang="en-gb">
  <head>
    <title>UK Energy Mix Live</title>
    <meta name="description" content="Shows the live status of Great Britain’s electric power transmission network">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="grid.css?<?= filemtime(__DIR__ . '/../../public/grid.css') ?>" type="text/css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="icon" href="favicon.svg?<?= floor(time() / 300) ?>" type="image/svg+xml">
    <script src="grid.js?<?= filemtime(__DIR__ . '/../../public/grid.js') ?>" defer></script>
  </head>
  <body>
    <main>
      <section id="introduction">
        <h1>UK Energy Mix Live</h1>
        <p>
          Live electricity generation, demand, imports, exports, prices and emissions for Great Britain
        </p>
      </section>

      <div id="status" class="columns">
        <section>
<?php Status::output($state->latest, Status::time($state->time), true); ?>
        </section>
        <section>
<?php Equation::output($state->latest, true); ?>
        </section>
      </div>

<?php Latest::output($state->latest); ?>
<?php Tabs::output($state); ?>
<?php About::output($state); ?>

    </main>

    <footer>
      <div>
        Independent website using third-party energy data.
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