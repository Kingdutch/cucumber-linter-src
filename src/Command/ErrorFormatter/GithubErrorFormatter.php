<?php

declare(strict_types=1);

namespace CucumberLinter\Command\ErrorFormatter;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Allow errors to be reported in pull-requests diff when run in a GitHub Action
 * @see https://help.github.com/en/actions/reference/workflow-commands-for-github-actions#setting-an-error-message
 */
class GithubErrorFormatter implements ErrorFormatter {

  private string $currentWorkingDirectory;

  public function __construct()
  {
    $currentWorkingDirectory = getcwd();
    assert($currentWorkingDirectory !== FALSE);
    $this->currentWorkingDirectory = $currentWorkingDirectory;
  }

  public function formatErrors(array $errors, InputInterface $input, OutputInterface $output): int
  {
    foreach ($errors as $featureErrors) {
      foreach ($featureErrors as $fileSpecificError) {
        $metas = [
          'file' => $this->getRelativePath($fileSpecificError->getFile()),
          'line' => $fileSpecificError->getLine(),
          'col' => 0,
        ];
        array_walk($metas, static function (&$value, string $key): void {
          $value = sprintf('%s=%s', $key, (string)$value);
        });

        $message = $fileSpecificError->getMessage();
        // newlines need to be encoded
        // see https://github.com/actions/starter-workflows/issues/68#issuecomment-581479448
        $message = str_replace("\n", '%0A', $message);

        $line = sprintf('::error %s::%s', implode(',', $metas), $message);

        $output->write($line, false, OutputInterface::OUTPUT_RAW);
        $output->writeln('', OutputInterface::OUTPUT_NORMAL);
      }
    }

    return count($errors) > 0 ? 1 : 0;
  }

  private function getRelativePath(string $filename): string
  {
    if ($this->currentWorkingDirectory !== '' && strpos($filename, $this->currentWorkingDirectory) === 0) {
      return str_replace('\\', '/', substr($filename, strlen($this->currentWorkingDirectory) + 1));
    }

    return str_replace('\\', '/', $filename);
  }

}
