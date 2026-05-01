<?php

declare(strict_types=1);

use Composer\Semver\VersionParser;
use Rector\Config\RectorConfig;

function parsePhpVersion(string $version, int $defaultPatch = 0): int {
  $parts = array_map('intval', explode('.', $version));

  return $parts[0] * 10000 + $parts[1] * 100 + ($parts[2] ?? $defaultPatch);
}

function parseDowngradePhpVersion(string $version): string {
  $parts = array_map('intval', explode('.', $version));

  return "php$parts[0]$parts[1]";
}

$targetPhpConstraint = getenv('TARGET_PHP_CONSTRAINT') ?: throw new \Exception("Must specify TARGET_PHP_CONSTRAINT");
$version = (new VersionParser())->parseConstraints($targetPhpConstraint)->getLowerBound()->getVersion();
$targetPhpVersionId = parsePhpVersion($version);
$downgradeSet = parseDowngradePhpVersion($version);

$configBuilder = RectorConfig::configure()
  ->withPaths([
    __DIR__ . '/../src',
    __DIR__ . '/../tests',
  ])
  ->withPhpVersion($targetPhpVersionId)
  ->withoutParallel()
  ;

call_user_func_array([$configBuilder, "withDowngradeSets"], [$downgradeSet => TRUE]);

return $configBuilder;
