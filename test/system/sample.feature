Feature: $featureName
  In order to $benefit
  As a $role
  I can $feature

  Background:
    Given some $background
    And some more $background

  Scenario: $scenarioDescription
    Given some $context
    And more $context
    When some $event
    And second $event
    Then $outcome
    And another $outcome
    But another $outcome

  @dev # will be run by --tags=@dev
  Scenario: $scenarioDescription
    Given some $context
    And more $context
    When some $event
    And second $event
    Then $outcome
    And another $outcome
    But another $outcome

  @javascript # will be run through selenium
  Scenario: $scenarioDescription
    Given some $context
    And more $context
    When some $event
    And second $event
    Then $outcome
    And another $outcome
    But another $outcome

  @skip # will not be run
  Scenario: $scenarioDescription
    Given some $context
    And more $context
    When some $event
    And second $event
    Then $outcome
    And another $outcome
    But another $outcome
