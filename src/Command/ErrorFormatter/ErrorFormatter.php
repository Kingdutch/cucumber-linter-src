<?php

declare(strict_types=1);

namespace CucumberLinter\Command\ErrorFormatter;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is the interface custom error formatters implement.
 *
 * @api
 */
interface ErrorFormatter {

  /**
   * Formats the errors and outputs them to the console.
   *
   * @param array<string, list<\CucumberLinter\LintError>> $errors
   *
   * @return int Error code.
   */
  public function formatErrors(
    array $errors,
    InputInterface $input,
    OutputInterface $output,
  ) : int;

}
