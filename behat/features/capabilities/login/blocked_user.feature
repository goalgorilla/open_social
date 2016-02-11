@login @security @stability @blocked_user
Feature:  Notification that account is blocked
  Benefit: In order to know what the problem is not able to login with correct credentials
  Role: AN
  Goal/desire: I want to get a message when I try to login but my account is blocked

  Scenario Login as blocked user
    Given I am on the homepage
    And I login as a blocked user with the name "test_blocked_user" and a correct password
    Then I should see "The username test_blocked_user has not been activated or is blocked."

  Scenario Login as blocked user with incorrect password
    Given I am on the homepage
    And I login as a blocked user with the name "test_blocked_user" and an incorrect password
    Then I should see "This could happen for one of for the following reasons:"
