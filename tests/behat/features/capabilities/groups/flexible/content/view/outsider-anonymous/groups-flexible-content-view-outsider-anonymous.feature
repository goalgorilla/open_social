@api @javascript @flexible-groups-content
Feature: Flexible groups content view access for anonymous users

  Background:
    Given I enable the module "social_group_flexible_group"
    And I disable that the registered users to be verified immediately

  Scenario: As anonymous user views a public topic in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | public                   | en       |
    And I am an anonymous user

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As anonymous user views a public event in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | public                   | 2100-01-01T12:00:00 | en       |
    And I am an anonymous user

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As anonymous user views a community topic in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | community                | en       |
    And I am an anonymous user

    When I am viewing the topic "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a community event in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And I am an anonymous user

    When I am viewing the event "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a group topic in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | group                    | en       |
    And I am an anonymous user

    When I am viewing the topic "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a group event in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | group                    | 2100-01-01T12:00:00 | en       |
    And I am an anonymous user

    When I am viewing the event "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a public topic in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | public                   | en       |
    And I am an anonymous user

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should not see "Test group"

  Scenario: As anonymous user views a public event in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And events wi
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | public                   | 2100-01-01T12:00:00 | en       |
    And I am an anonymous user

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should not see "Test group"

  Scenario: As anonymous user views a community topic in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | community                | en       |
    And I am an anonymous user

    When I am viewing the topic "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a community event in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And I am an anonymous user

    When I am viewing the event "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a group topic in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | group                    | en       |
    And I am an anonymous user

    When I am viewing the topic "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a group event in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | group                    | 2100-01-01T12:00:00 | en       |
    And I am an anonymous user

    When I am viewing the event "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a public topic in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                         |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | public                   | en       |
    And I am an anonymous user

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should not see "Test group"

  Scenario: As anonymous user views a public event in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                         |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | public                   | 2100-01-01T12:00:00 | en       |
    And I am an anonymous user

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should not see "Test group"

  Scenario: As anonymous user views a community topic in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                       |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | community                | en       |
    And I am an anonymous user

    When I am viewing the topic "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a community event in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                       |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And I am an anonymous user

    When I am viewing the event "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a group topic in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                       |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | group                    | en       |
    And I am an anonymous user

    When I am viewing the topic "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a group event in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                       |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | group                    | 2100-01-01T12:00:00 | en       |
    And I am an anonymous user

    When I am viewing the event "Test content"

    Then I should be asked to login

  Scenario: As anonymous user views a group event on overview page
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And events with non-anonymous author:
      | title                   | group      | body                  | field_content_visibility  | field_event_date    | langcode |
      | This is public event    | Test group | Body description text | public                    | 2100-01-01T12:00:00 | en       |
      | This is community event | Test group | Body description text | community                 | 2100-01-01T12:00:00 | en       |
      | This is secret event    | Test group | Body description text | group                     | 2100-01-01T12:00:00 | en       |
    And I am an anonymous user

    When I am on the event overview

    Then I should see "This is public event"
      And I should not see "This is community event"
      And I should not see "This is secret event"

  Scenario: As anonymous user views a group topic on overview page
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And topics with non-anonymous author:
      | title                   | group      | field_topic_type | body       | field_content_visibility | langcode |
      | This is public topic    | Test group | News             | body text  | public                   | en       |
      | This is community topic | Test group | News             | body text  | community                | en       |
      | This is secret topic    | Test group | News             | body text  | group                    | en       |
    And I am an anonymous user

    When I am on the topic overview

    Then I should see "This is public topic"
      And I should not see "This is community topic"
      And I should not see "This is secret topic"