@api @javascript
Feature: Enroll for an event without an account
  Benefit: In order to attend an Event
  Role: AN
  Goal/desire: I want to be able to enroll to an event without having to create an account

  @AN
  Scenario: Successfully enroll to an event as AN user
    Given I enable the module "social_event_an_enroll"
    And event content:
      | title         | field_event_date | status | field_content_visibility | field_event_an_enroll |
      | AN Event 1    | +2 days          | 1      | public                   | 1                     |

    When I open the "event" node with title "AN Event 1"
    And I should see "AN Event 1" in the "Hero block"
    And I should see the link "Enroll" in the "Hero block"
    And I click "Enroll"
    And I wait for AJAX to finish
    And I should see the link "Log in" in the "Modal"
    And I should see the link "Create an account" in the "Modal"
    And I should see the link "Enroll as guest" in the "Modal"
    And I click "Enroll as guest"
    And I wait for AJAX to finish
    And I should see "Enroll in AN Event 1 Event" in the ".ui-dialog-title" element
    And I fill in the following:
      | First name    | John         |
      | Last name     | Doe          |
      | Email address | john@doe.com |
    And I press "Enroll in event" in the "Modal"
    And I wait for AJAX to finish
    And I should see the text "Meetup: AN Event 1" in the "Modal"
    And I press the "Close" button
    And I should see "AN Event 1" in the "Hero block"
    And I should not see the link "Enroll" in the "Hero block"

    # Cancel enrollment.
    And I should see "AN Event 1" in the "Hero block"
    And I should see the button "Enrolled"
#    @todo Uncomment lines below when Firefox will have 48+ version in Selenium.
#    When I press the "Enrolled" button
#    And I press "Cancel enrollment"
#    And I wait for AJAX to finish
#    Then I should see the success message "You are no longer enrolled in this event. Your personal data used for the enrollment is also deleted."
    And I reload the page
    And I should see "AN Event 1" in the "Hero block"
#    And I should see the link "Enroll" in the "Hero block"
    # Duplicate Enrollment.
#    When I click "Enroll"
#    And I wait for AJAX to finish
#    When I click "Enroll as guest"
#    And I wait for AJAX to finish
#    Then I should see "Enroll in AN Event 1 Event" in the ".ui-dialog-title" element
#    And I fill in the following:
#      | First name    | John         |
#      | Last name     | Doe          |
#      | Email address | john@doe.com |
#    And I press "Enroll in event" in the "Modal"
#    And I wait for AJAX to finish
#    Then I should see the text "Meetup: AN Event 1" in the "Modal"
#    And I press the "Close" button
#    Given I am an anonymous user
#    And I open the "event" node with title "AN Event 1"
#    When I click "Enroll"
#    And I wait for AJAX to finish
#    When I click "Enroll as guest"
#    And I wait for AJAX to finish
#    And I fill in the following:
#      | First name    | John         |
#      | Last name     | Doe          |
#      | Email address | john@doe.com |
#    And I press "Enroll in event"
#    Then I should see the success message "You have been already enrolled to this event. You have also received a notification via email"
    # AS CM+ I should see Guest enrollments.
    And I am logged in as a user with the "contentmanager" role
    And I open the "event" node with title "AN Event 1"
    And I click "Manage enrollments"
    And I should see "John Doe"
    # AS Verified I should not see Guest enrollments emails
    And I am logged in as an "verified"
    And I open the "event" node with title "AN Event 1"
    And I should not see "Manage enrollments"

  @AN
  Scenario: Control the site-wide default of AN enrollment
    Given I enable the module "social_event_an_enroll"
    And I am an anonymous user
    And I am viewing an event:
      | title                    | No guest enrollment |
      | field_event_date         | +3 days             |
      | field_event_date_end     | +4 days             |
      | field_content_visibility | public              |
    And I press "Enroll"
    And I should not see "Enroll as guest"

    ##
    ## In this test the vent must be created using the form because we are
    ## testing the effect of a hook_form_alter
    ##
    And I set the configuration item "social_event_an_enroll.settings" with key "event_an_enroll_default_value" to 1
    And I am logged in as a user with the "verified" role
    And I am on "node/add/event"
    And I fill in the custom fields for this "event"
    And I fill in the following:
      | Title                                  | Anonymous event enrollment |
      | edit-field-event-date-0-value-date     | 2125-01-01                 |
      | edit-field-event-date-end-0-value-date | 2125-01-01                 |
      | edit-field-event-date-0-value-time     | 11:00:00                   |
      | edit-field-event-date-end-0-value-time | 11:00:00                   |
      | Location name                          | GG HQ                      |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I click the xth "0" element with the css "[for=edit-field-content-visibility-public]"
    And I press "Create event"

    And I am an anonymous user
    And I open the "event" node with title "Anonymous event enrollment"
    And I click "Enroll"
    And I should see "Enroll in Anonymous event Enrollment"
    And I should see "Enroll as guest"

  @see-event-enrollments-list
  Scenario: LUs with different languages are able to see a list of all event enrollments

    Given I enable the module "social_language"
    And I enable the module "social_event_an_enroll"

    And users:
      | name           | mail                     | status | roles       |
      | sm             | sm@example.com           | 1      | sitemanager |
      | Dude (English) | event_user_1@example.com | 1      |             |
      | Dude (Dutch)   | event_user_2@example.com | 1      |             |

    And I am logged in as an "authenticated user"
    And I am viewing my event:
      | title                    | My Behat Event |
      | field_event_date         | +8 days        |
      | status                   | 1              |
      | field_content_visibility | public         |
      | langcode                 | und            |

    # Add Dutch language.
    When I am logged in as an "administrator"
    And I turn off translations import
    And I am on "/admin/config/regional/language"
    And I should see the text "Add language"
    And I click the xth "0" element with the css ".local-actions .button--action"
    And I select "Dutch" from "Language name"
    And I press "Add language"
    And I wait for AJAX to finish

    # Let's enable User language detection is enabled.
    And I go to "/admin/config/regional/language/detection"
    And I check the box "Enable user language detection method"
    And I press "Save settings"

    # Language field on user form should be visible when site has more than one
    # language and the User language detection is enabled.
    And I am logged in as "Dude (Dutch)"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I should see the text "Interface language"
    And I should see the text "Select the language you want to use this site in."
    And I select "Dutch" from "Interface language"
    And I press "Save"

    # Add created users directly to the event.
    And I am logged in as "sm"
    And I am on "/community-events"
    And I click "My Behat Event"
    And I click "Manage enrollments"
    # Add a first one.
    And I should see "Add enrollees"
    And I click the xth "1" element with the css ".btn.dropdown-toggle"
    And I click "Add directly"
    And I should see "Find people by name or email address"
    And I fill in select2 input ".form-type-select" with "event_user_1@example.com" and select "event_user_1@example.com"
    And I press "Save"
    # Add a second one.
    And I should see "Add enrollees"
    And I click the xth "1" element with the css ".btn.dropdown-toggle"
    And I click "Add directly"
    And I should see "Find people by name or email address"
    And I fill in select2 input ".form-type-select" with "event_user_2@example.com" and select "event_user_2@example.com"
    And I press "Save"

    # Check a list of all event enrollments by the English user.
    And I am logged in as "Dude (English)"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My events"
    And I click "My Behat Event"
    And I should see the link "All enrollments"
    And I click "All enrollments"
    And I should see "Dude (English)"
    And I should see "Dude (Dutch)"

    # Check a list of all event enrollments by the Dutch user.
    And I am logged in as "Dude (Dutch)"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My events"
    And I click "My Behat Event"
    And I should see the link "All enrollments"
    And I click "All enrollments"
    And I should see "Dude (English)"
    And I should see "Dude (Dutch)"
