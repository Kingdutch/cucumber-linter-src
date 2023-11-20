Feature: Demonstrate incorrect indentation

  Scenario: Incorrect indentation
    Given an initial statement
      And a statement that's then indented

    When I make the next statement
  And I indent it incorrectly

    Then I should see errors
        And there should be three of them
