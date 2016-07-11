@api @account @login @stability @blocked_user @AN @perfect @security @DS-238
Feature: Notification that account is blocked
  Benefit: In order to know what the problem is not able to login with correct credentials
  Role: AN
  Goal/desire: I want to get a message when I try to login but my account is blocked

  Scenario: Login as a blocked user
    Given users:
      | name      | status | pass |
      | User Case |      0 | UseCase123 |
    And I am on the homepage
    When I visit "?q=user/login"
    And I fill in the following:
      | Username or email address | User Case |
      | Password | UseCase123 |
    And I press "Log in"
    Then I should see the error message "The username User Case has not been activated or is blocked."
    And I should not see the error message "This could happen for one of for the following reasons"

  Scenario: Login as blocked user with incorrect password
    Given users:
      | name      | status | pass |
      | User Case |      0 | wrongpassword |
    And I am on the homepage
    When I click "Log in"
    And I fill in the following:
      | Username or email address | User Case |
      | Password | UseCase123 |
    And I press "Log in"
    Then I should not see the error message "The username User Case has not been activated or is blocked."
    And I should see "This could happen for one of for the following reasons"
