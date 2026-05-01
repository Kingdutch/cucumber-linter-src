<?php

declare(strict_types=1);

namespace CucumberLinter\Tests;

use CucumberLinter\Command\ErrorFormatter\GithubErrorFormatter;
use CucumberLinter\LintError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class GithubErrorFormatterTest extends TestCase {

  protected function tearDown() : void {
    putenv('CUCUMBER_LINTER_GITHUB_FILE_PREFIX');
    unset($_ENV['CUCUMBER_LINTER_GITHUB_FILE_PREFIX'], $_SERVER['CUCUMBER_LINTER_GITHUB_FILE_PREFIX']);
    parent::tearDown();
  }

  public function testGithubFilePrefixPrependedToAnnotationPath() : void {
    putenv('CUCUMBER_LINTER_GITHUB_FILE_PREFIX=applications/community_management_system');

    $fixturePath = realpath(__DIR__ . '/../data/noerrors.feature');
    self::assertNotFalse($fixturePath);
    $cwd = getcwd();
    self::assertNotFalse($cwd);
    self::assertStringStartsWith($cwd, $fixturePath);

    $errors = [
      $fixturePath => [
        new LintError('example message', $fixturePath, 1),
      ],
    ];

    $formatter = new GithubErrorFormatter();
    $output = new BufferedOutput();
    self::assertSame(1, $formatter->formatErrors($errors, new StringInput(''), $output));

    $expectedRelative = str_replace('\\', '/', substr($fixturePath, strlen($cwd) + 1));
    $expectedFile = 'applications/community_management_system/' . $expectedRelative;
    $raw = $output->fetch();
    self::assertStringContainsString('file=' . $expectedFile, $raw);
    self::assertStringContainsString('::error file=', $raw);
  }

  public function testGithubFilePrefixTrailingSlashesAreNormalized() : void {
    putenv('CUCUMBER_LINTER_GITHUB_FILE_PREFIX=applications/community_management_system///');

    $fixturePath = realpath(__DIR__ . '/../data/noerrors.feature');
    self::assertNotFalse($fixturePath);
    $cwd = getcwd();
    self::assertNotFalse($cwd);

    $errors = [
      $fixturePath => [
        new LintError('msg', $fixturePath, 2),
      ],
    ];

    $formatter = new GithubErrorFormatter();
    $output = new BufferedOutput();
    $formatter->formatErrors($errors, new StringInput(''), $output);

    $expectedRelative = str_replace('\\', '/', substr($fixturePath, strlen($cwd) + 1));
    $expectedFile = 'applications/community_management_system/' . $expectedRelative;
    $raw = $output->fetch();
    self::assertStringContainsString('file=' . $expectedFile, $raw);
    self::assertStringNotContainsString('community_management_system///', $raw);
  }

  public function testEmptyGithubFilePrefixEnvLeavesPathUnchanged() : void {
    putenv('CUCUMBER_LINTER_GITHUB_FILE_PREFIX=   ');

    $fixturePath = realpath(__DIR__ . '/../data/noerrors.feature');
    self::assertNotFalse($fixturePath);
    $cwd = getcwd();
    self::assertNotFalse($cwd);

    $errors = [
      $fixturePath => [
        new LintError('msg', $fixturePath, 3),
      ],
    ];

    $formatter = new GithubErrorFormatter();
    $output = new BufferedOutput();
    $formatter->formatErrors($errors, new StringInput(''), $output);

    $expectedRelative = str_replace('\\', '/', substr($fixturePath, strlen($cwd) + 1));
    $raw = $output->fetch();
    self::assertStringContainsString('file=' . $expectedRelative, $raw);
    self::assertStringNotContainsString('file=/' . $expectedRelative, $raw);
  }

}
