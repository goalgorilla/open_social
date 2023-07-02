@api @javascript @flexible-groups-content
Feature: Flexible groups content view access for contentmanager users

  Background:
    Given I enable the module "social_group_flexible_group"
    And I disable that the registered users to be verified immediately

  Scenario: As contentmanager user views a public topic in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | public                   | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a public event in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | public                   | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a community topic in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | community                | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a community event in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a group topic in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | group                    | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a group event in a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | public                          |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | group                    | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a public topic in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | public                   | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a public event in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | public                   | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a community topic in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | community                | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a community event in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a group topic in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | group                    | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a group event in a community group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | community                       |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | group                    | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a public topic in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                         |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | public                   | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a public event in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                         |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | public                   | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a community topic in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                       |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | community                | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a community event in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                       |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a group topic in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                       |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | group                    | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Scenario: As contentmanager user views a group event in a secret group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | members                       |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | group                    | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the contentmanager role

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"
