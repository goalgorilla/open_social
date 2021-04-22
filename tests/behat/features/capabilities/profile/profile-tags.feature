@api @profile @user @members @stability @YANG-5091 @stability-4 @profile-tag
Feature: Add profile tags to the user profiles
  Benefit: In order to add profile tags
  Role: CM
  Goal/desire: I want to be able to add profile tags

  Scenario: Disable access to add profile tags
    Given users:
      | name   | status | uid |
      | Member | 1      | 999 |
    Then I am logged in as an "sitemanager"
    And I go to "/admin/config/people/social-profile"
    And I uncheck the box "Allow profiles to be tagged"
    And I press "Save configuration"
    And I go to "/user/999/profile"
    Then I should not see the text "Profile tag"

  Scenario: Enable profile tags without split
    Given "profile_tag" terms:
      | name                | parent |
      | Behat Profile tag 1 |        |
      | Behat Profile tag 2 |        |
    Given users:
      | name   | status | uid |
      | Member | 1      | 999 |
    Then I am logged in as an "sitemanager"
    And I go to "/admin/config/people/social-profile"
    And I check the box "Allow profiles to be tagged"
    And I uncheck the box "Allow category split"
    And I press "Save configuration"
    Then I am logged in as an "contentmanager"
    And I go to "/user/999/profile"
    And I select "Behat Profile tag 1" from "Profile tag"
    And I additionally select "Behat Profile tag 2" from "Profile tag"
    And I press "Save"
    And I go to "/user/999/information"
    Then I should see "Profile tags"
    Then I should see "Behat Profile tag 1"
    Then I should see "Behat Profile tag 2"

  Scenario: Enable profile tag split
    Given "profile_tag" terms:
      | name                  | parent              |
      | Behat Profile tag 1   |                     |
      | Behat Profile tag 1.1 | Behat Profile tag 1 |
      | Behat Profile tag 1.2 | Behat Profile tag 1 |
      | Behat Profile tag 2   |                     |
      | Behat Profile tag 2.1 | Behat Profile tag 2 |
      | Behat Profile tag 2.2 | Behat Profile tag 2 |
    Given users:
      | name   | status | uid |
      | Member | 1      | 999 |
    Then I am logged in as an "sitemanager"
    And I go to "/admin/config/people/social-profile"
    And I check the box "Allow profiles to be tagged"
    And I check the box "Allow category split"
    And I press "Save configuration"
    Then I am logged in as an "contentmanager"
    And I go to "/user/999/profile"
    And I select "Behat Profile tag 1.1" from "Behat Profile tag 1"
    And I additionally select "Behat Profile tag 1.2" from "Behat Profile tag 1"
    And I select "Behat Profile tag 2.1" from "Behat Profile tag 2"
    And I additionally select "Behat Profile tag 2.2" from "Behat Profile tag 2"
    And I press "Save"
    And I go to "/user/999/information"
    Then I should see "Behat Profile tag 1"
    Then I should see "Behat Profile tag 1.1"
    Then I should see "Behat Profile tag 1.2"
    Then I should see "Behat Profile tag 2"
    Then I should see "Behat Profile tag 2.1"
    Then I should see "Behat Profile tag 2.2"

  Scenario: Allow to select parents
    Given "profile_tag" terms:
      | name                  | parent              |
      | Behat Profile tag 1   |                     |
      | Behat Profile tag 1.1 | Behat Profile tag 1 |
      | Behat Profile tag 2   |                     |
      | Behat Profile tag 2.1 | Behat Profile tag 2 |
    Given users:
      | name   | status | uid |
      | Member | 1      | 999 |
    Then I am logged in as an "sitemanager"
    And I go to "/admin/config/people/social-profile"
    And I check the box "Allow profiles to be tagged"
    And I check the box "Allow category split"
    And I check the box "Allow parents to be used as tag"
    And I press "Save configuration"
    Then I am logged in as an "contentmanager"
    And I go to "/user/999/profile"
    And I select "Behat Profile tag 1" from "Behat Profile tag 1"
    And I additionally select "Behat Profile tag 1.1" from "Behat Profile tag 1"
    And I select "Behat Profile tag 2" from "Behat Profile tag 2"
    And I additionally select "Behat Profile tag 2.1" from "Behat Profile tag 2"
    And I press "Save"
    And I go to "/user/999/information"
    Then I should see "Behat Profile tag 1"
    Then I should see "Behat Profile tag 1.1"
    Then I should see "Behat Profile tag 2"
    Then I should see "Behat Profile tag 2.1"
