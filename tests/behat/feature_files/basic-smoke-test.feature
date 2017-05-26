Feature: Basic smoke test
  As a developer starting a new Slim application
  I want a very basic app to return a response
  So that I can begin my development

  Scenario: Nascent application returning a response
    Given I have set up an application to return a response with Hello World
    When I query the route '/hello-world'
    Then the response body should be 'Hello world'

  Scenario: Using echoing instead of returning a response object
    Given I have set up an application to echo Hello World
    When I query the route '/hello-world'
    Then the response body should be 'Hello world'
