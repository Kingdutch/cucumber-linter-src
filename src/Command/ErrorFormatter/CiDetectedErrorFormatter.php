<?php

declare(strict_types=1);

namespace CucumberLinter\Command\ErrorFormatter;

use OndraM\CiDetector\CiDetector;
use OndraM\CiDetector\Exception\CiNotDetectedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @api */
class CiDetectedErrorFormatter implements ErrorFormatter
{

  public function __construct(
    private GithubErrorFormatter $githubErrorFormatter,
  )
  {
  }

  public function formatErrors(array $errors, InputInterface $input, OutputInterface $output): int
  {
    $ciDetector = new CiDetector();

    try {
      $ci = $ciDetector->detect();
      if ($ci->getCiName() === CiDetector::CI_GITHUB_ACTIONS) {
        return $this->githubErrorFormatter->formatErrors($errors, $input, $output);
      }
    } catch (CiNotDetectedException) {
      // pass
    }

    return count($errors) > 0 ? 1 : 0;
  }

}
