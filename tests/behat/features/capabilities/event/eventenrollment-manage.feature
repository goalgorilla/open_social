@api @event @eventenrollment @stability @perfect @GPI-10 @profile @stability-2 @enrollment-manage
Feature: Manage event enrollment
  Benefit: In order to attend an Event
  Role: LU
  Goal/desire: I want to manage event enrollment

  @LU
  Scenario: Successfully manage enrollment
    Given users:
      | name            | pass            | mail                        | status | roles        |
      | event_creator   | event_creator   | event_creator@example.com   | 1      | sitemanager  |
      | event_organiser | event_organiser | event_organiser@example.com | 1      |              |
      | event_enrollee  | event_enrollee  | event_enrollee@example.com  | 1      |              |
    When I am logged in as "event_creator"
    Given event content:
      | title           | field_event_date | field_event_date_end | status | field_content_visibility | alias         |
      | My Behat Event  | +8 days          | +9 days              | 1      | community                | /mybehatevent |

    When I am logged in as "event_organiser"
    And I wait for "3" seconds
    And I am on "mybehatevent"
    Then I should not see the link "Manage enrollments"

    Given I enable the module "social_event_managers"
    When I am logged in as "event_creator"
    And I am on "mybehatevent"
    And I click "Edit content"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I fill in "event_organiser" for "field_event_managers[0][target_id]"
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
    And I should see the link "Enrollee"
    And I should see the link "Organization"
    And I should see the link "Enroll date"
    And I should see the text "Operation"
    And I should see the link "event_enrollee"

    # as EO we should also get a notification about this enrollment.Ability:
    When I am logged in as "event_organiser"
    And I wait for the queue to be empty
    And I am at "notifications"
    Then I should see text matching "event_enrollee has enrolled to the event My Behat Event you are organizing"