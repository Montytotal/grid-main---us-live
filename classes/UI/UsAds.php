<?php

namespace KateMorley\Grid\UI;

class UsAds {
  private const CLIENT_ENV = 'GOOGLE_ADSENSE_CLIENT';
  private const DEFAULT_CLIENT = 'ca-pub-9859724881253160';
  private const SLOTS = [
    'top' => 'GOOGLE_ADSENSE_SLOT_TOP',
    'mid' => 'GOOGLE_ADSENSE_SLOT_MID',
    'left' => 'GOOGLE_ADSENSE_SLOT_LEFT',
    'right' => 'GOOGLE_ADSENSE_SLOT_RIGHT',
  ];

  public static function outputHeadScript(): void {
    $client = self::client();

    if ($client === '') {
      return;
    }
?>
    <script
      async
      src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($client, ENT_QUOTES, 'UTF-8') ?>"
      crossorigin="anonymous"
    ></script>
<?php
  }

  public static function outputSlot(string $slot): void {
    $client = self::client();
    $slotId = self::slotId($slot);

    if ($client === '' || $slotId === '') {
      return;
    }
?>
      <div class="us-ad us-ad-<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>">
        <ins
          class="adsbygoogle"
          style="display:block"
          data-ad-client="<?= htmlspecialchars($client, ENT_QUOTES, 'UTF-8') ?>"
          data-ad-slot="<?= htmlspecialchars($slotId, ENT_QUOTES, 'UTF-8') ?>"
          data-ad-format="<?= self::format($slot) ?>"
          data-full-width-responsive="<?= self::isSideRail($slot) ? 'false' : 'true' ?>"
        ></ins>
        <script>
          (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
      </div>
<?php
  }

  public static function adsTxt(): ?string {
    $publisher = self::publisherId();

    if ($publisher === '') {
      return null;
    }

    return 'google.com, '
      . $publisher
      . ', DIRECT, f08c47fec0942fa0'
      . "\n";
  }

  private static function client(): string {
    $client = trim((string)getenv(self::CLIENT_ENV));

    if (preg_match('/^pub-[0-9]+$/', $client)) {
      $client = 'ca-' . $client;
    }

    if (preg_match('/^ca-pub-[0-9]+$/', $client)) {
      return $client;
    }

    return self::DEFAULT_CLIENT;
  }

  private static function publisherId(): string {
    $client = self::client();

    if (preg_match('/^ca-(pub-[0-9]+)$/', $client, $matches)) {
      return $matches[1];
    }

    if (preg_match('/^pub-[0-9]+$/', $client)) {
      return $client;
    }

    return '';
  }

  private static function slotId(string $slot): string {
    if (!isset(self::SLOTS[$slot])) {
      return '';
    }

    return trim((string)getenv(self::SLOTS[$slot]));
  }

  private static function format(string $slot): string {
    return self::isSideRail($slot) ? 'vertical' : 'auto';
  }

  private static function isSideRail(string $slot): bool {
    return $slot === 'left' || $slot === 'right';
  }
}
