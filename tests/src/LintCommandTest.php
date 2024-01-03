<?php

declare(strict_types=1);

namespace CucumberLinter\Tests;

use CucumberLinter\Command\ErrorFormatter\CiDetectedErrorFormatter;
use CucumberLinter\Command\ErrorFormatter\GithubErrorFormatter;
use CucumberLinter\Command\ErrorFormatter\TableErrorFormatter;
use CucumberLinter\Command\LintCommand;
use CucumberLinter\Linter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class LintCommandTest extends TestCase {

  protected LintCommand $command;

  protected function setUp() : void {
    $linter = Linter::getInstance();
    $githubErrorFormatter = new GithubErrorFormatter();
    $ciErrorFormatter = new CiDetectedErrorFormatter($githubErrorFormatter);
    $errorFormatter = new TableErrorFormatter($ciErrorFormatter);

    $this->command = new LintCommand(
      $linter,
      $errorFormatter
    );
  }

  /**
   * Ensure a correctly formatted feature produces no errors as output.
   */
  public function testValidatedFeatureProducesNoErrors() : void {
    $input = new StringInput(__DIR__ . "/../data/noerrors.feature");
    $output = new BufferedOutput();

    $this::assertEquals(0, $this->command->run($input, $output));
    $this::assertStringContainsString('[OK] No errors', $output->fetch());
  }

  /**
   * Ensure a feature with one error produces error output.
   */
  public function testIncorrectFeatureReturnsNonZero() : void {
    $input = new StringInput(__DIR__ . "/../data/background-empty.feature");
    $output = new BufferedOutput();

    $this::assertEquals(1, $this->command->run($input, $output));
    $this::assertStringNotContainsString('[OK] No errors', $output->fetch());
  }

}
