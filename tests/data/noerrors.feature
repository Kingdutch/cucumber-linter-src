@api @javascript
Feature: A feature with no errors

  Scenario: All good!
    Given we start correctly

    # Comments should be ignored for blank line separation
    # because they visually belong to the block below them.
    When we add a new blank line

    Then we'll get no feedback from the tool
