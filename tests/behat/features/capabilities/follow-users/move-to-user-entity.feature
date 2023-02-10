@api @javascript @no-database
Feature: Move storage from profile entity to user entity

  Background:
    Given the fixture "move-to-user-entity.feature.sql"
    And I run pending updates

  Scenario: A user is still following after the update
    # We must login manually since the user is in the database and not
    # registered in the UserManager of the DrupalExtension.
    Given I am on "/user/login"
    And I fill in "Username or email address" with "follower"
    And I fill in "Password" with "follower"
    And I press "Log in"

    When I am on the profile of "followed"

    Then I should see "Unfollow" in the "#block-socialblue-profile-statistic-block .follow-user-wrapper" element

  Scenario: A user is still allowed to follow after the update
    Given I am on "/user/login"
    And I fill in "Username or email address" with "followed"
    And I fill in "Password" with "followed"
    And I press "Log in"

    When I am on the profile of "follower"

    Then I should see "Follow" in the "#block-socialblue-profile-statistic-block .follow-user-wrapper" element

  Scenario: A user is still allowed to unfollow after the update with following disabled
    Given I am on "/user/login"
    And I fill in "Username or email address" with "disallowed_follower"
    And I fill in "Password" with "disallowed_follower"
    And I press "Log in"

    When I am on the profile of "disallowed_following"

    Then I should see "Unfollow" in the "#block-socialblue-profile-statistic-block .follow-user-wrapper" element

  Scenario: A user is still not allowed to follow after the update
    Given I am on "/user/login"
    And I fill in "Username or email address" with "follower"
    And I fill in "Password" with "follower"
    And I press "Log in"

    When I am on the profile of "disallowed_following"

    And I should not see "Follow" in the "#block-socialblue-profile-statistic-block .follow-user-wrapper" element
    And I should not see "Unfollow" in the "#block-socialblue-profile-statistic-block .follow-user-wrapper" element
