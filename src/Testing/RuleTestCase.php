<?php

declare(strict_types=1);

namespace CucumberLinter\Testing;

use CucumberLinter\Linter;
use CucumberLinter\LintError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

abstract class RuleTestCase extends TestCase  {

  private ?Linter $linter = NULL;

  private function getLinter() : Linter {
    if ($this->linter === null) {
      $this->linter = Linter::getInstance();
    }

    return $this->linter;
  }

  /**
   * @param string[] $files
   * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
   */
  public function lint(array $files, array $expectedErrors) : void {
    $actualErrors = $this->gatherLintErrors($files);
    $strictlyTypedSprintf = static function (int $line, string $message, ?string $tip) : string {
      $message = sprintf('%02d: %s', $line, $message);
      if ($tip !== null) {
        $message .= "\n    💡 " . $tip;
      }
      return $message;
    };
    $expectedErrors = array_map(static function (array $error) use($strictlyTypedSprintf) : string {
      return $strictlyTypedSprintf($error[1], $error[0], $error[2] ?? null);
    }, $expectedErrors);
    $actualErrors = array_map(static function (LintError $error) use($strictlyTypedSprintf) : string {
      $line = $error->getLine();
      if ($line === null) {
        return $strictlyTypedSprintf(-1, $error->getMessage(), $error->getTip());
      }
      return $strictlyTypedSprintf($line, $error->getMessage(), $error->getTip());
    }, $actualErrors);
    self::assertSame(implode("\n", $expectedErrors) . "\n", implode("\n", $actualErrors) . "\n");
  }

  /**
   * @param string[] $files
   *
   * @return list<\CucumberLinter\LintError>
   */
  public function gatherLintErrors(array $files) : array {
    $canonicalize = [Path::class, 'canonicalize'];
    $files = array_map($canonicalize, $files);
    $errors = [];
    foreach ($files as $file) {
      $errors[] = $this->getLinter()->lint($file);
    }

    return array_merge(...$errors);
  }

}
