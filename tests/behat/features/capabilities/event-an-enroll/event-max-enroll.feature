@api @javascript @event @eventenrollment @stability @perfect @TB-1764 @profile @stability-3 @event-max-enroll
Feature: Limitation event enrollments
  Benefit: In order to attend an Event
  Role: As a Verified
  Goal/desire: I want to limit event enrollments

  @verified
  Scenario: Successfully limited event enrollments
    Given I enable the module "social_event_max_enroll"
    When I am logged in as a user with the "sitemanager" role
    And I am on "admin/config/opensocial/event-max-enroll"
    Then I should see the text "Maximum Event Enrollment settings"
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
    Then I should see "0 people have enrolled"
    And I should not see "0 people have enrolled (7 spots left)"
    When I click "Edit content"
    Then I should see unchecked the box "Set a limit to number of enrollees"
    And I should see "Set a limit to number of enrollees" in the "#enrollment" element
    And I should not see "Maximum number of enrollees" in the "#enrollment" element
    And I should not see "Set a limit for event enrollments. Users are not able to enroll once the maximum number of enrollees is reached." in the "#enrollment" element
    When I check the box "Set a limit to number of enrollees"
    Then I should see "Maximum number of enrollees" in the "#enrollment" element
    And I should see "Set a limit for event enrollments. Users are not able to enroll once the maximum number of enrollees is reached." in the "#enrollment" element
    When I fill in "field_event_max_enroll_num[0][value]" with "2"
    And I press "Save"
    Then I should see "0 people have enrolled (2 spots left)"
    When I press "Enroll"
    Then I should not see "0 people have enrolled (2 spots left)"
    And I should see "1 people have enrolled (1 spot left)"

    Given users:
      | name              | mail                     | status | roles    |
      | First Behat User  | behat_user_1@example.com | 1      | verified |
      | Second Behat User | behat_user_2@example.com | 1      | verified |
    When I am logged in as "First Behat User"
    And I click "All Upcoming events"
    And I click "My Behat Event"
    And I press "Enroll"
    Then I should not see "1 people have enrolled (1 spot left)"
    And I should see "2 people have enrolled (0 spots left)"
    And I should see the button "Enrolled"

    When I am logged in as "Second Behat User"
    And I click "All Upcoming events"
    And I click "My Behat Event"
