@account @profile @stability @AN @perfect @DS-701 @api @stability-1
Feature: I want to have a profile
  Benefit: In order to present myself to other people
  Role: LU
  Goal/desire: I want to have a profile

  @critical
  Scenario: Successfully edit profile
    Given users:
      | name              | mail                     | status |
      | Edit Profile      | edit_profile@example.com | 1      |
    And I am logged in as "Edit Profile"
    And I am on "/user"
    And I click "Edit profile information"
    When I fill in the following:
      | First name | Edit |
      | Last name  | Profile |
      | Function   | Behat test |
      | Organization | GoalGorilla |
      | Phone number | 911 |
    And I select "UA" from "Country"
    And I wait for AJAX to finish
    Then I should see "City"
    And I fill in the following:
         | City | Lviv |
         | Street address | Fedkovycha 60a |
         | Postal code | 79000 |
         | Oblast | Lviv oblast |
    And I fill in the "edit-field-profile-self-introduction-0-value" WYSIWYG editor with "Self intro text."
    And I press "Save"
    Then I should see "Edit"
    And I should see "Profile"
#    And I should see "GoalGorilla"
#    And I should see "Behat test"
    And I should see "911"
    And I should see "Fedkovycha 60a"
    And I should see "79000"
    And I should see "Lviv"
    And I should see "Lviv oblast"
    And I should see "Self intro text"
