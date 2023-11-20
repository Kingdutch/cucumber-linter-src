Feature: Keywords must be in the right order

  Scenario: Duplicate Given
    Given the keyword Given is used

    Given it is used again

  Scenario: Keyword Then before When
    Given we start correctly

    Then we skip When

  Scenario: Keyword When repeated
    Given we start correctly

    When we try something

    When we repeat ourselves

  Scenario: Keyword Then repeated
    Given we start correctly

    When we try something

    Then we move correctly to assertions

    Then we assert again
