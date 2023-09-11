@api @group @PROD-21532 @stability @stability-1
Feature: Revisions are disabled for open group
  Benefit: So I can't see revisions tab as they are not supported.
  Role: As a Verified
  Goal/desire: I don't want to see revisions tab when editing a group.

  Scenario: Successfully edit group and don't see revisions tab
    Given users:
      | name              | mail             | field_profile_organization | status | roles    |
      | Group Manager One | gm_1@example.com | GoalGorilla                | 1      | verified |
      | Group Member Two  | gm_2@example.com | Drupal                     | 1      | verified |
    And I am logged in as "Group Manager One"
    And I am on "group/add"
    And I press "Continue"
    And I wait for AJAX to finish
    When I fill in "Title" with "Test open group"
    And I wait for "1" seconds
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "1 member"

    Given I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My groups"
    When I click "Test open group" in the "Main content"
    Then I should see "Test open group"
    Then I should not see "Revisions"
