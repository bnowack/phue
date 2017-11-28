Feature: Application scaffold
  In order to create applications quickly
  As a developer
  I get a working app scaffold out of the box

  Scenario: Accessing the homepage
    When I go to "/"
    Then I should see "Welcome!"

  Scenario: Getting a response for non-existing pages
    When I go to "/does-not-exist"
    And I should see "404" in the "title" element
    And the response should contain "No route found"

  Scenario: Retrieving a manifest file for mobile phones
    When I go to "/manifest.json"
    Then the response should be valid JSON

  @javascript
  Scenario: Navigating back to the homepage afer a 404
    When I go to "/does-not-exist"
    And I click on the header logo
    Then I should be on the homepage
