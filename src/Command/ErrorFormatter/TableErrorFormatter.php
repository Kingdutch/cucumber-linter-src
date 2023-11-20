<?php

declare(strict_types=1);

namespace CucumberLinter\Command\ErrorFormatter;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TableErrorFormatter implements ErrorFormatter {

  public function formatErrors(array $errors, InputInterface $input, OutputInterface $output): int {
    $style = new SymfonyStyle($input, $output);

    if ($errors === []) {
      $style->success('No errors');

      return 0;
    }

    foreach ($errors as $feature => $featureErrors) {
      $rows = [];
      foreach ($featureErrors as $error) {
        $message = $error->getMessage();
        if ($error->getTip() !== null) {
          $tip = $error->getTip();

          $message .= "\n";
          if (str_contains($tip, "\n")) {
            $lines = explode("\n", $tip);
            foreach ($lines as $line) {
              $message .= '💡 ' . ltrim($line, ' •') . "\n";
            }
          } else {
            $message .= '💡 ' . $tip;
          }
        }
        $rows[] = [
          $this->formatLineNumber($error->getLine()),
          $message,
        ];
      }

      $style->table(['Line', $feature], $rows);
    }

    return 1;
  }

  private function formatLineNumber(?int $lineNumber): string {
    if ($lineNumber === null) {
      return '';
    }

    $isRunningInVSCodeTerminal = getenv('TERM_PROGRAM') === 'vscode';
    if ($isRunningInVSCodeTerminal) {
      return ':' . $lineNumber;
    }

    return (string) $lineNumber;
  }

}
