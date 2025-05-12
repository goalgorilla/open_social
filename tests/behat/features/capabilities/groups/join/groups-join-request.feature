@api @javascript
Feature: A group can be configured to require membership to be requested

  Background:
    Given I enable the module social_group_flexible_group
    And I enable the module social_group_request
    And groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | Public visibility       | flexible_group | en       | public                          | request                         |

  Scenario Outline: The feature is available for everyone that can create groups for public visibility
    Given I am logged in as a user with the <role> role

    When I view the group creation page
    And I select the radio button "Public"

    Then I should see "Request to join"

  Examples:
    | role           |
    | authenticated  |
    | verified       |
    | contentmanager |
    | sitemanager    |

  Scenario Outline: The feature is available for everyone that can create groups for community visibility
    Given I am logged in as a user with the <role> role

    When I view the group creation page
    And I select the radio button "Community"

    Then I should see "Request to join"

  Examples:
    | role           |
    | authenticated  |
    | verified       |
    | contentmanager |
    | sitemanager    |

#  @todo Scenario requires a step that checks whether a radio button is disabled.
#  Scenario: The feature is not available regardless of role for groups with member visibility
#    Given I am logged in as a user with the verified role
#
#    When I view the group creation page
#    And I select the radio button "Group members only (secret)"
#
#    Then I should not be able to select the radio button "Request to join"

  Scenario: As an anonymous user I am asked to login when I try to request access to a public group
    Given I am an anonymous user

    When I am viewing the about page of group "Test group"
    And I click "Request to join"

    Then I should see "Request to join"
    And I should see "In order to send your request, please first sign up or log in."
    And I should see the button "Sign up"
    And I should see the button "Log in"

  Scenario: As an anonymous user I can not visit the anonymous request callback URL directly
    Given I am an anonymous user

    When I am viewing the "anonymous-request-membership" page of group "Test group"

    Then I should see "Test group"
    And I should see the link "Request to join"

  Scenario Outline: As a regular user, content manager or site manager I must request access to a group configured with request to join
    Given I am logged in as a user with the <role> role

    When I am viewing the group "Test group"
    And I click "Request to join"
    And I wait for AJAX to finish
    And I press "Send request" in the "Modal"

    Then I should see "Your request has been sent successfully"

  Examples:
    | role           |
    | authenticated  |
    | verified       |
    | contentmanager |
    | sitemanager    |

  Scenario: A user can not request membership while a previous request is pending
    Given users:
      | name           | mail                | status | roles    |
      | Pending Member | pending@example.com | 1      | verified |
    And group membership requests:
      | group      | user           |
      | Test group | Pending Member |
    And I am logged in as "Pending Member"

    When I am viewing the group "Test group"

    Then I should not see "Request to join"
    And I should see "Request sent"

  Scenario: A user can request membership after a previous request has been rejected
    Given users:
      | name            | mail                 | status | roles    |
      | Rejected Member | rejected@example.com | 1      | verified |
    And group membership requests:
      | group      | user            | status   |
      | Test group | Rejected Member | rejected |
    And I am logged in as "Rejected Member"

    When I am viewing the group "Test group"
    And I click "Request to join"
    And I wait for AJAX to finish
    And I press "Send request" in the "Modal"

    Then I should see "Your request has been sent successfully"

  Scenario: A user can request membership after a previous request has been approved but they left the group
    Given users:
      | name            | mail                 | status | roles    |
      | Approved Member | approved@example.com | 1      | verified |
    And group membership requests:
      | group      | user            | status   |
      | Test group | Approved Member | approved |
    And I am logged in as "Approved Member"

    When I am viewing the group "Test group"
    And I click "Request to join"
    And I wait for AJAX to finish
    And I press "Send request" in the "Modal"

    Then I should see "Your request has been sent successfully"
