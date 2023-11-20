<?php

declare(strict_types=1);

namespace CucumberLinter\Internal;

final class ComposerHelper {

  private static ?string $linterVersion = null;

  public static function getLinterVersion(): string
  {
    if (self::$linterVersion !== null) {
      return self::$linterVersion;
    }

    $installed = require __DIR__ . '/../../vendor/composer/installed.php';
    $rootPackage = $installed['root'] ?? null;
    if ($rootPackage === null) {
      return self::$linterVersion = 'Unknown version';
    }

    if (preg_match('/[^v\d.]/', $rootPackage['pretty_version']) === 0) {
      // Handles tagged versions, see https://github.com/Jean85/pretty-package-versions/blob/2.0.5/src/Version.php#L31
      return self::$linterVersion = $rootPackage['pretty_version'];
    }

    return self::$linterVersion = $rootPackage['pretty_version'] . '@' . substr((string) $rootPackage['reference'], 0, 7);
  }
}
