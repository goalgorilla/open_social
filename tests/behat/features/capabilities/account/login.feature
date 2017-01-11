@api @account @login @security @stability @AN @perfect @DS-233
Feature: Login
  Benefit: In order to participate
  Role: AN
  Goal/desire: Log in with my e-mail

  @critical
  Scenario: Successfully login with e-mail
    Given users:
      | name             | status | pass             | mail                         |
      | test_email_login |      1 | test_email_login | test_email_login@example.com |
    And I am on the homepage
    When I click "User menu"
    And I click "Log in"
    And I fill in the following:
      | Username or email address | test_email_login@example.com |
      | Password | test_email_login |
    And I press "Log in"
    And I click "Profile of test_email_login"
    Then I should see "My profile"
    And I should see "test_email_login"

  @security
  Scenario: unsuccessful login without leaking data
    Given I am an anonymous user
    And I am on the homepage
    When I click "User menu"
    And I click "Log in"
    And I fill in the following:
      | Username or email address | test@test.com |
      | Password | test |
    And I press "Log in"
    Then I should not see the following error messages:
      | error messages |
      | Unrecognized username or password |
      | There have been more than 5 failed login attempts for this account. It is temporarily blocked |
    And I should see the error message "This could happen for one of for the following reasons:"
