<?php

namespace CucumberLinter;

use Cucumber\Gherkin\GherkinParser;
use Cucumber\Messages\Background;
use Cucumber\Messages\GherkinDocument;
use Cucumber\Messages\Scenario;

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
        $errors[] = $this->lintBackground($document->uri, $child->background);
      }
      else if ($child->scenario !== NULL) {
        $errors[] = $this->lintScenario($document->uri, $child->scenario);
      }
    }

    return array_merge(...$errors);
  }

  /**
   * @return \CucumberLinter\LintError[]
   */
  private function lintBackground(string $feature, Background $background) : array {
    if ($background->steps === []) {
      return [new LintError("A background must not be declared if it's empty.", $feature, $background->location->line)];
    }

    $errors = [];

    // @todo This does not take internationalization into account.
    $startStep = $background->steps[0];
    if ($startStep->keyword !== "Given ") {
      $errors[] = new LintError("The first step in a Background must be 'Given'", $feature, $background->steps[0]->location->line);
    }

    for ($i=1, $count=count($background->steps); $i<$count; $i++) {
      $step = $background->steps[$i];
      if ($step->keyword !== "And ") {
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
  private function lintScenario(string $feature, Scenario $scenario) : array {
    $errors = [];
    $has_when = FALSE;
    $has_then = FALSE;

    // @todo This does not take internationalization into account.
    $startStep = $scenario->steps[0];
    if ($startStep->keyword !== "Given ") {
      $errors[] = new LintError("The first step in a Scenario must be 'Given'", $feature, $scenario->steps[0]->location->line);
    }

    for ($i=1, $count=count($scenario->steps); $i<$count; $i++) {
      $step = $scenario->steps[$i];
      if ($step->location->column !== $startStep->location->column) {
        $errors[] = new LintError("Steps in a scenario beyond the first one should be indented on the same level as the first one. Expected {$startStep->location->column} spaces, got {$step->location->column}.", $feature, $step->location->line);
      }

      // @todo This does not take internationalization into account.
      if ($step->keyword === "Given ") {
        $errors[] = new LintError("'Given' must only occur once in a scenario to signify the arrange stage of the test. Using it multiple times is an indicator you might want multiple scenarios. Link multiple arrange actions using 'And'.", $feature, $step->location->line);
      }
      // @todo This does not take internationalization into account.
      elseif ($step->keyword === "When ") {
        if ($has_when) {
          $errors[] = new LintError("Found duplicate 'When' keyword. 'When' should be used to signal the act stage of the test, link multiple actions using 'And'.", $feature, $step->location->line);
        }
        else {
          $has_when = TRUE;
          $previous_line = $scenario->steps[$i-1]->location->line + count($scenario->steps[$i-1]->dataTable?->rows ?? []);
          $blank_lines = $step->location->line - $previous_line - 1;
          if ($blank_lines !== 1) {
            $errors[] = new LintError("Expected 1 blank line before start of the 'act' block, found $blank_lines.", $feature, $step->location->line);
          }
        }
      }
      // @todo This does not take internationalization into account.
      elseif ($step->keyword === "Then ") {
        if (!$has_when) {
          $errors[] = new LintError("Found 'Then' keyword before 'When'. 'Then' should be used to signal the assertion stage of the test.", $feature, $step->location->line);
        }
        elseif ($has_then) {
          $errors[] = new LintError("Found duplicate 'Then' keyword. 'Then' should be used to signal the assertion stage of the test, link multiple assertions using 'And'.", $feature, $step->location->line);
        }
        else {
          $has_then = TRUE;
          $previous_line = $scenario->steps[$i-1]->location->line + count($scenario->steps[$i-1]->dataTable?->rows ?? []);
          $blank_lines = $step->location->line - $previous_line - 1;
          if ($blank_lines !== 1) {
            $errors[] = new LintError("Expected 1 blank line before start of the 'assert' block, found $blank_lines.", $feature, $step->location->line);
          }
        }
      }
    }

    return $errors;
  }

  /**
   * Private constructor for singleton pattern.
   */
  private function __construct() {
    $this->parser = new GherkinParser();
  }

}
