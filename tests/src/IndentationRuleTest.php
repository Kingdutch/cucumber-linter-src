<?php

declare(strict_types=1);

namespace CucumberLinter\Tests;

use CucumberLinter\Testing\RuleTestCase;

class IndentationRuleTest extends RuleTestCase {

  public function testRule() : void {
    $this->lint([__DIR__ . "/../data/indentation-error.feature"], [
      [
        "Steps in a scenario beyond the first one should be indented on the same level as the first one. Expected 5 spaces, got 7.",
        5,
      ],
      [
        "Steps in a scenario beyond the first one should be indented on the same level as the first one. Expected 5 spaces, got 3.",
        8,
      ],
      [
        "Steps in a scenario beyond the first one should be indented on the same level as the first one. Expected 5 spaces, got 9.",
        11,
      ],
    ]);
  }

}
