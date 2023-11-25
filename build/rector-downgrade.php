<?php

declare(strict_types=1);

use Composer\Semver\VersionParser;
use Rector\Config\RectorConfig;
use Rector\DowngradePhp81\Rector\FunctionLike\DowngradePureIntersectionTypeRector;
use Rector\DowngradePhp81\Rector\Property\DowngradeReadonlyPropertyRector;
use Rector\DowngradePhp82\Rector\Class_\DowngradeReadonlyClassRector;

return static function (RectorConfig $config): void {
  $parsePhpVersion = static function (string $version, int $defaultPatch = 0): int {
    $parts = array_map('intval', explode('.', $version));

    return $parts[0] * 10000 + $parts[1] * 100 + ($parts[2] ?? $defaultPatch);
  };

  $targetPhpConstraint = getenv('TARGET_PHP_CONSTRAINT') ?: throw new \Exception("Must specify TARGET_PHP_CONSTRAINT");
  $targetPhpVersionId = $parsePhpVersion((new VersionParser())->parseConstraints($targetPhpConstraint)->getLowerBound()->getVersion());

  $config->paths([
    __DIR__ . '/../src',
    __DIR__ . '/../tests',
  ]);
  $config->phpVersion($targetPhpVersionId);
  $config->disableParallel();

  if ($targetPhpVersionId < 80200) {
    $config->rule(DowngradeReadonlyClassRector::class);
  }

  if ($targetPhpVersionId < 80100) {
    $config->rule(DowngradeReadonlyPropertyRector::class);
    $config->rule(DowngradePureIntersectionTypeRector::class);
  }
};
