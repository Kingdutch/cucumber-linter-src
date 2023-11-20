Feature: Stages of a scenario are correctly separated by a blank line

  Scenario: No blank lines used
    Given we start correctly
    When we don't add a blank line
    Then we'll get some feedback

  Scenario: No blank line after a table
    Given we start correctly
    And some fixture
      | user |
      | john |
    When we forget a blank line

    Then we should get an error
