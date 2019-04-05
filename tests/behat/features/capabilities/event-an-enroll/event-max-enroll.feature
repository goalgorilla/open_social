@api @javascript @event @eventenrollment @stability @perfect @TB-1764 @profile @stability-3 @event-max-enroll
Feature: Limitation event enrollments
  Benefit: In order to attend an Event
  Role: LU
  Goal/desire: I want to limit event enrollments

  @LU
  Scenario: Successfully limited event enrollments
    Given I enable the module "social_event_max_enroll"
    When I am logged in as a user with the "sitemanager" role
    And I am on "admin/config/opensocial"
    Then I should see the link "Maximum Event EnrollmentSettings for Social Event Max Enroll module."
    When I click "Maximum Event EnrollmentSettings for Social Event Max Enroll module."
    Then I should be on "admin/config/opensocial/event-max-enroll"
    And I should see the text "Maximum Event Enrollment settings"
    And I should see checked the box "Enable maximum number of event enrollments"
    And I should see unchecked the box "Maximum event enrollments field is required"
    And I should see the button "Save configuration"
    When I am viewing my event:
      | title                    | My Behat Event |
      | field_event_date         | +8 days        |
      | field_event_date_end     | +9 days        |
      | body                     | Description    |
      | status                   | 1              |
      | field_content_visibility | community      |
    And I click "Edit content"
    Then I should see unchecked the box "Set a limit to number of participants"
    And I should see "Set a limit to number of participants" in the "#enrollment" element
    And I should see "Maximum number of enrollees" in the "#enrollment" element
    And I should see "Set a limit for event enrollments. Users are not able to enroll once the maximum number of enrollees is reached." in the "#enrollment" element
