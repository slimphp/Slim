Feature: Middlewares
  As a developer adding new functionality to an existing Slim application
  I want middlewares to run in my app
  So that I can have nice shiny decoupled software architecture

  Scenario: Adding a middleware, using closures
    Given I have set up an application to display ':-('
    And I have added a middleware closure that adds ' :-)' to the end of the output
    When I query the relevant route
    Then the response body should be ':-( :-)'
