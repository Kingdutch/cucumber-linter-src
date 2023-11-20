<?php

declare(strict_types=1);

namespace CucumberLinter\Command;

use CucumberLinter\Command\ErrorFormatter\TableErrorFormatter;
use CucumberLinter\Linter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Lint all *.feature files in a folder.
 */
class LintCommand extends Command {
  protected static $defaultName = 'lint';

  public function __construct(
    private Linter $linter,
    private TableErrorFormatter $errorFormatter,
  ) {
    parent::__construct();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $feature_files = [];

    $finder = (new Finder())->files()->name("*.feature");

    $features = $input->getArgument('features');
    assert(is_array($features));

    foreach ($features as $feature) {
      assert(is_string($feature));
      if (is_dir($feature)) {
        foreach ($finder->in($feature) as $file) {
          $feature_files[] = $file->getPathname();
        }
        continue;
      }
      if (!is_file($feature)) {
        throw new \InvalidArgumentException("Could not find '$feature'");
      }
      $feature_files[] = $feature;
    }

    $progressBar = new ProgressBar($output, count($feature_files));

    $errors = [];
    foreach ($feature_files as $feature) {
      $errors[$feature] = $this->linter->lint($feature);
      $progressBar->advance();
    }

    $progressBar->finish();

    return $this->errorFormatter->formatErrors($errors, $input, $output);
  }

  protected function configure() : void {
    $this
      ->addArgument('features', InputArgument::REQUIRED | InputArgument::IS_ARRAY, "The feature file(s) or folder(s) to lint.")
    ;
  }

}
