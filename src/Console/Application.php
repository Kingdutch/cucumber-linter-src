<?php

declare(strict_types=1);

namespace CucumberLinter\Console;

use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication {

  /**
   * @param iterable<int,\Symfony\Component\Console\Command\Command> $commands
   */
  public function __construct(iterable $commands = []) {
    parent::__construct("Cucumber Linter - Cucumber and Behat Static Analysis Tool", "0.1.0");
    foreach ($commands as $command) {
      $this->add($command);
    }
    assert(is_countable($commands));
    $this->setDefaultCommand("lint", count($commands) === 1);
  }

}
