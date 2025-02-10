@api
Feature: View likes
  Benefit: In order to view who liked content
  Role: As a Verified
  Goal/desire: I want to be able to view who liked content

  Background:
    Given groups with non-anonymous owner:
      | label        | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Public group | Group description       | flexible_group | en       | public                          |
      | Member group | Group description       | flexible_group | en       | members                         |
    And topics with non-anonymous author:
      | title          | group        | field_topic_type | body                  | field_content_visibility | langcode |
      | Public content | Public group | News             | Body description text | public                   | en       |
      | Group content  | Member group | News             | Body description text | group                    | en       |
    And users:
      | name         | mail                | field_profile_first_name   | field_profile_last_name   | status | roles    |
      | Liker        | pending@example.com | me                         | likey                     | 1      | verified |
    And likes node:
      | title          | bundle  | author  |
      | Public content | topic   | Liker   |
      | Group content  | topic   | Liker   |

  Scenario: As verified user I can view likes for a public topic in a public group
    Given I am logged in as a user with the verified role

    When I am viewing who liked the topic "Public content"

    Then I should see "Me likey"

  Scenario: As verified user I can't view likes for topic in a closed group
    Given I am logged in as a user with the verified role

    When I am viewing who liked the topic "Group content"

    Then I should be denied access
