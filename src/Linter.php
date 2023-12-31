<?php

namespace CucumberLinter;

use Cucumber\Gherkin\GherkinParser;
use Cucumber\Messages\Background;
use Cucumber\Messages\GherkinDocument;
use Cucumber\Messages\Scenario;
use Cucumber\Messages\Step\KeywordType;

final class Linter {

  private static ?self $linter = NULL;

  private GherkinParser $parser;

  private const ALLOWED_TAGS = [
    '@api',
    '@javascript',
    '@no-database',
    '@no-install',
    '@no-update',
    '@disabled',
  ];

  /**
   * Get the linter instance.
   *
   * @return static
   *   The instance of the linter for this application.
   */
  public static function getInstance() : static {
    if (static::$linter === NULL) {
      static::$linter = new static();
    }
    return static::$linter;
  }

  /**
   * @return \CucumberLinter\LintError[]
   */
  public function lint(string $feature) : array {
    if (!file_exists($feature)) {
      throw new \InvalidArgumentException("Could not find '$feature'.");
    }
    $feature_contents = file_get_contents($feature);
    if ($feature_contents === FALSE) {
      throw new \RuntimeException("Failed to read contents of '$feature'.");
    }
    $pickles = $this->parser->parseString($feature, $feature_contents);
    $parseErrors = [];
    foreach ($pickles as $envelope) {
      if ($envelope->parseError !== NULL) {
        // @todo This shouldn't be a lint error but a non-ignorable error.
        // @todo This doesn't have test coverage yet.
        $parseErrors[] = new LintError(
          $envelope->parseError->message,
          $feature,
          $envelope->parseError->source->location?->line,
        );
      }

      // If there are parse errors already recorded they're all we display.
      if ($parseErrors !== []) {
        continue;
      }

      // We only want the whole document message for this feature file. That
      // allows us to traverse to the rest.
      if ($envelope->gherkinDocument === NULL) {
        continue;
      }

      return $this->lintDocument($envelope->gherkinDocument);
    }

    return $parseErrors;
  }

  /**
   * @return \CucumberLinter\LintError[]
   */
  private function lintDocument(GherkinDocument $document) : array {
    assert($document->uri !== NULL, "Can not lint anonymous files.");

    $errors = [];

    // Ensure tags are used for control flow rather than organization.
    /** @var array<\Cucumber\Messages\Tag> $tags */
    $tags = $document->feature?->tags ?? [];
    foreach ($tags as $tag) {
      if ($tag->name !== "" && !in_array($tag->name, self::ALLOWED_TAGS, TRUE)) {
        $errors[] = [new LintError("Tag '{$tag->name}' is not a valid behat control tag.", $document->uri, $tag->location->line, NULL, "Use folders rather than tags for test organization.")];
      }
    }

    $children = $document->feature?->children ?? [];
    foreach ($children as $child) {
      if ($child->background !== NULL) {
        $errors[] = $this->lintBackground($document, $document->uri, $child->background);
      }
      else if ($child->scenario !== NULL) {
        $errors[] = $this->lintScenario($document, $document->uri, $child->scenario);
      }
    }

    return array_merge(...$errors);
  }

  /**
   * @return \CucumberLinter\LintError[]
   */
  private function lintBackground(GherkinDocument $document, string $feature, Background $background) : array {
    if ($background->steps === []) {
      return [new LintError("A background must not be declared if it's empty.", $feature, $background->location->line)];
    }

    $errors = [];

    $startStep = $background->steps[0];
    if ($startStep->keywordType !== KeywordType::CONTEXT) {
      $errors[] = new LintError("The first step in a Background must be 'Given'", $feature, $background->steps[0]->location->line);
    }

    for ($i=1, $count=count($background->steps); $i<$count; $i++) {
      $step = $background->steps[$i];
      if ($step->keywordType !== KeywordType::CONJUNCTION) {
        $errors[] = new LintError("Steps in a Background beyond the first one should start with 'And'", $feature, $step->location->line);
      }

      if ($step->location->column !== $startStep->location->column) {
        $errors[] = new LintError("Steps in a Background beyond the first one should be indented on the same level as the first one. Expected {$startStep->location->column} spaces, got {$step->location->column}.", $feature, $step->location->line);
      }
    }

    return $errors;
  }

  /**
   * @return \CucumberLinter\LintError[]
   */
  private function lintScenario(GherkinDocument $document, string $feature, Scenario $scenario) : array {
    $errors = [];
    $has_when = FALSE;
    $has_then = FALSE;

    $startStep = $scenario->steps[0];
    if ($startStep->keywordType !== KeywordType::CONTEXT) {
      $errors[] = new LintError("The first step in a Scenario must be 'Given'", $feature, $scenario->steps[0]->location->line);
    }

    for ($i=1, $count=count($scenario->steps); $i<$count; $i++) {
      $step = $scenario->steps[$i];
      if ($step->location->column !== $startStep->location->column) {
        $errors[] = new LintError("Steps in a scenario beyond the first one should be indented on the same level as the first one. Expected {$startStep->location->column} spaces, got {$step->location->column}.", $feature, $step->location->line);
      }

      if ($step->keywordType === KeywordType::CONTEXT) {
        $errors[] = new LintError("'Given' must only occur once in a scenario to signify the arrange stage of the test. Using it multiple times is an indicator you might want multiple scenarios. Link multiple arrange actions using 'And'.", $feature, $step->location->line);
      }
      elseif ($step->keywordType === KeywordType::ACTION) {
        if ($has_when) {
          $errors[] = new LintError("Found duplicate 'When' keyword. 'When' should be used to signal the act stage of the test, link multiple actions using 'And'.", $feature, $step->location->line);
        }
        else {
          $has_when = TRUE;
          $blank_lines = $this->calculateBlankLinesBeforeStep($document, $scenario, $i);
          if ($blank_lines !== 1) {
            $errors[] = new LintError("Expected 1 blank line before start of the 'act' block, found $blank_lines.", $feature, $step->location->line);
          }
        }
      }
      elseif ($step->keywordType === KeywordType::OUTCOME) {
        if (!$has_when) {
          $errors[] = new LintError("Found 'Then' keyword before 'When'. 'Then' should be used to signal the assertion stage of the test.", $feature, $step->location->line);
        }
        elseif ($has_then) {
          $errors[] = new LintError("Found duplicate 'Then' keyword. 'Then' should be used to signal the assertion stage of the test, link multiple assertions using 'And'.", $feature, $step->location->line);
        }
        else {
          $has_then = TRUE;
          $blank_lines = $this->calculateBlankLinesBeforeStep($document, $scenario, $i);
          if ($blank_lines !== 1) {
            $errors[] = new LintError("Expected 1 blank line before start of the 'assert' block, found $blank_lines.", $feature, $step->location->line);
          }
        }
      }
    }

    return $errors;
  }

  /**
   * Calculate the number of blank lines between a step and the previous step.
   *
   * This takes into account data tables for the previous step and also any
   * comments that might've been placed above the step we're looking at (which
   * are not counted as blank lines).
   *
   * @param \Cucumber\Messages\GherkinDocument $document
   *   The entire gherkin document.
   * @param \Cucumber\Messages\Scenario $scenario
   *   The scenario we're currently looking at.
   * @param positive-int $stepNumber
   *   The current step number to look at.
   *
   * @return non-negative-int
   *   The number of blank lines between the current step and the previous one.
   */
  private function calculateBlankLinesBeforeStep(GherkinDocument $document, Scenario $scenario, int $stepNumber) : int {
    assert(isset($scenario->steps[$stepNumber - 1], $scenario->steps[$stepNumber]));
    $previous_step = $scenario->steps[$stepNumber - 1];
    $current_step = $scenario->steps[$stepNumber];

    $previous_step_line = $previous_step->location->line + count($previous_step->dataTable?->rows ?? []);

    $comments_between_lines = 0;
    /** @var \Cucumber\Messages\Comment $comment */
    foreach ($document->comments as $comment) {
      // If the comment line is before or on our previous step line the comment
      // is too soon.
      if ($comment->location->line <= $previous_step_line) {
        continue;
      }
      // The comments are in order so if we're at or past our current step line
      // we don't need to look at the rest.
      if ($comment->location->line >= $current_step->location->line) {
        break;
      }
      // We're in between our two steps so this is a comment.
      $comments_between_lines++;
    }

    $line_breaks_between_steps = $current_step->location->line - $previous_step_line - $comments_between_lines;
    assert($line_breaks_between_steps >= 1, "For some reason the previous line was at or after the current line.");

    // -1 because we care about blank lines but different steps are always 1
    // linebreak apart, so blank lines are the line differences above 1.
    return $line_breaks_between_steps - 1;
  }

  /**
   * Private constructor for singleton pattern.
   */
  private function __construct() {
    $this->parser = new GherkinParser();
  }

}
