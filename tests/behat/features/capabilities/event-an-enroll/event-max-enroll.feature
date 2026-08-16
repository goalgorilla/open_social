@api @javascript
Feature: Limitation event enrollments
  Benefit: In order to attend an Event
  Role: As a Verified
  Goal/desire: I want to limit event enrollments

  @verified
  Scenario: Successfully limited event enrollments
    Given I enable the module "social_event_max_enroll"

    And I am logged in as a user with the "sitemanager" role
    And I am on "admin/config/opensocial/event-max-enroll"
    And I should see the text "Maximum Event Enrollment settings"
    And I should see checked the box "Enable maximum number of event enrollments"
    And I should see unchecked the box "Maximum event enrollments field is required"
    And I should see the button "Save configuration"
    And events authored by current user:
      | title          | body        | field_event_date | field_event_date_end | status | field_content_visibility |
      | My Behat Event | Description | +8 days          | +9 days              | 1      | community                |
    And I am viewing the event "My Behat Event"
    And I should see "0 people have enrolled"
    And I should not see "0 people have enrolled (7 spots left)"
    And I click "Edit content"
    And I select "NL" from "Country"
    And I wait for AJAX to finish
    And I should see unchecked the box "Set a limit to number of enrollees"
    And I should see "Set a limit to number of enrollees" in the "#enrollment" element
    And I should not see "Maximum number of enrollees" in the "#enrollment" element
    And I should not see "Set a limit for event enrollments. Users are not able to enroll once the maximum number of enrollees is reached." in the "#enrollment" element
    And I check the box "Set a limit to number of enrollees"
    And I should see "Maximum number of enrollees" in the "#enrollment" element
    And I should see "Set a limit for event enrollments. Users are not able to enroll once the maximum number of enrollees is reached." in the "#enrollment" element
    And I fill in "field_event_max_enroll_num[0][value]" with "2"
    And I press "Save"
    And I should see "0 people have enrolled (2 spots left)"
    And I press "Enroll"
    And I wait for AJAX to finish
    And I reload the page
    And I should not see "0 people have enrolled (2 spots left)"
    And I should see "1 person has enrolled (1 spot left)"

    And users:
      | name              | mail                     | status | roles    |
      | First Behat User  | behat_user_1@example.com | 1      | verified |
      | Second Behat User | behat_user_2@example.com | 1      | verified |
    And I am logged in as "First Behat User"
    And I click "All Upcoming events"
    And I click "My Behat Event"
    And I press "Enroll"
    And I wait for AJAX to finish
    And I reload the page
    And I should not see "1 person has enrolled (1 spot left)"
    And I should see "2 people have enrolled (0 spots left)"
    And I should see the button "Enrolled"

    And I am logged in as "Second Behat User"
    And I click "All Upcoming events"
    And I click "My Behat Event"
