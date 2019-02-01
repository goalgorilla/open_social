@api @event @eventenrollment @stability @perfect @GPI-10 @profile @stability-2
Feature: Manage event enrollment
  Benefit: In order to attend an Event
  Role: LU
  Goal/desire: I want to manage event enrollment

  @LU
  Scenario: Successfully manage enrollment
    Given users:
      | name            | pass            | mail                        | status |
      | event_creator   | event_creator   | event_creator@example.com   | 1      |
      | event_organiser | event_organiser | event_organiser@example.com | 1      |
      | event_enrollee  | event_enrollee  | event_enrollee@example.com  | 1      |
    When I am logged in as "event_creator"
    And I am viewing my event:
      | title                    | My Behat Event |
      | field_event_date         | +8 days        |
      | field_event_date_end     | +9 days        |
      | status                   | 1              |
      | field_content_visibility | community      |
      | alias                    | /mybehatevent  |
    Then I should not see the link "Manage enrollments"

    When I enable the module "social_event_managers"
      And I am on "mybehatevent"
    Then I should not see the link "Manage enrollments"

    When I am logged in as "event_organiser"
    And I wait for "3" seconds
    And I am on "mybehatevent"
    Then I should not see the link "Manage enrollments"

    When I am logged in as "event_creator"
    And I am on "mybehatevent"
    And I click "Edit content"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I fill in "Event organisers" with "event" and select "event_organiser"
    And I press "Save"
    And I am logged in as "event_organiser"
    And I am on "mybehatevent"
    Then I should see the link "Manage enrollments"

    When I click "Manage enrollments"
    Then I should see the text "0 Enrollees"
    And I should see the text "No one has enrolled for this event"

    When I am logged in as "event_enrollee"
    And I am on "mybehatevent"
    And I press "Enroll"
    And I am logged in as "event_organiser"
    And I am on "mybehatevent"
    And I click "Manage enrollments"
    Then I should see the text "1 Enrollees"
    And I should see the text "no members are selected"
    And I should see the text "See selected members on other pages"
    And I should see the link "Enrollee"
    And I should see the link "Organization"
    And I should see the link "Enroll date"
    And I should see the text "Operation"
    And I should see the text "Operation"
    And I should see the link "event_enrollee"
