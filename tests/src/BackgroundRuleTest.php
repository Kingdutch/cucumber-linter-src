<?php

declare(strict_types=1);

namespace CucumberLinter\Tests;

use CucumberLinter\Testing\RuleTestCase;

class BackgroundRuleTest extends RuleTestCase {

  public function testEmpty() : void {
    $this->lint([__DIR__ . "/../data/background-empty.feature"], [
      [
        "A background must not be declared if it's empty.",
        3,
      ],
    ]);
  }

  public function testNonEmpty() : void {
    $this->lint([__DIR__ . "/../data/background-incorrect-steps.feature"], [
      [
        "Steps in a Background beyond the first one should start with 'And'",
        5,
      ],
      [
        "Steps in a Background beyond the first one should be indented on the same level as the first one. Expected 5 spaces, got 7.",
        5,
      ],
    ]);

  }

}
