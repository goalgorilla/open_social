@api @group @stability @DS-811 @DS-816
Feature: Create Open Group
  Benefit: So I can work together with others in a relative small circle
  Role: As a LU
  Goal/desire: I want to create Open Groups

  Scenario: Successfully create open group
    Given I am logged in as an "authenticated user"
    And I am on "user"
    And I click "Groups"
    And I click "Add a group"
    When I fill in "Title" with "Test open group"
    And I fill in "edit-field-group-description-0-value" with "Description text"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "Description text"
    And I should see "1 member"
    And I should see "Joined"
    And I should see the link "Read more"

    # DS-761 As a LU I want to view the hero area of a group
    And I click "Test open group"
    And I should see "Test open group" in the "Hero block"
    And I should see "Description text" in the "Hero block"
    And I should see "1 member" in the "Hero block"
    And I should see the button "Joined"
    And I click the xth "1" element with the css ".dropdown-toggle"
    And I should see the link "Leave group"
    And I should see the link "Edit group" in the "Hero block"

