@api @javascript
Feature: Private message recipient search by profile name
  Benefit: Find members when composing a private message
  Role: As a verified member
  Goal/desire: I can find recipients by first or last name, not only username

  Background:
    Given I enable the module "social_private_message"
    And users:
      | name       | mail                        | status | field_profile_first_name | field_profile_last_name | roles    |
      | pm_sender  | pm_sender@example.localhost | 1      | Sender                   | User                    | verified |
      | ironman    | ironman@example.localhost   | 1      | Tony                     | Stark                   | verified |

  Scenario: To autocomplete matches profile first name, last name and username
    Given I am logged in as "pm_sender"

    When I go to "/private-message/create"

    Then Autocomplete suggestions for "To" with the filled value "tony" should include "Tony Stark"
    And Autocomplete suggestions for "To" with the filled value "stark" should include "Tony Stark"
    And Autocomplete suggestions for "To" with the filled value "iron" should include "Tony Stark"
