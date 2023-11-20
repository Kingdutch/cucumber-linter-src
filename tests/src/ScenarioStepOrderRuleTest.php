<?php

declare(strict_types=1);

namespace CucumberLinter\Tests;

use CucumberLinter\Testing\RuleTestCase;

class ScenarioStepOrderRuleTest extends RuleTestCase {

  public function testFirstGiven() : void {
    $this->lint([__DIR__ . "/../data/scenario-incorrect-start.feature"], [
      [
        "The first step in a Scenario must be 'Given'",
        7,
      ],
      [
        "The first step in a Scenario must be 'Given'",
        10,
      ],
      [
        "The first step in a Scenario must be 'Given'",
        13,
      ],
    ]);
  }

  public function testKeywordOrder() : void {
    $this->lint([__DIR__ . "/../data/scenario-keyword-order.feature"], [
      [
        "'Given' must only occur once in a scenario to signify the arrange stage of the test. Using it multiple times is an indicator you might want multiple scenarios. Link multiple arrange actions using 'And'.",
        6,
      ],
      [
        "Found 'Then' keyword before 'When'. 'Then' should be used to signal the assertion stage of the test.",
        11,
      ],
      [
        "Found duplicate 'When' keyword. 'When' should be used to signal the act stage of the test, link multiple actions using 'And'.",
        18,
      ],
      [
        "Found duplicate 'Then' keyword. 'Then' should be used to signal the assertion stage of the test, link multiple assertions using 'And'.",
        27,
      ],
    ]);
  }

  public function testKeywordFormatting() : void {
    $this->lint([__DIR__ . "/../data/scenario-stage-separation.feature"], [
      [
        "Expected 1 blank line before start of the 'act' block, found 0.",
        5,
      ],
      [
        "Expected 1 blank line before start of the 'assert' block, found 0.",
        6,
      ],
      [
        "Expected 1 blank line before start of the 'act' block, found 0.",
        13,
      ],
    ]);
  }

}
