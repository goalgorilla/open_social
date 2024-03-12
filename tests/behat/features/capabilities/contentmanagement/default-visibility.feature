@api @javascript
Feature: Default Content Visibility
  Benefit: In order to control the distribution of information and to secure my privacy
  Role: As a Verified and Anonymous
  Goal/desire: I want to see the visibility of content created with a default visibility

  Scenario: Default content visibility public as VU on the homepage
    Given I set the configuration item "entity_access_by_field.settings" with key "default_visibility" to "public"
    And I am logged in as an "sitemanager"
    And I create a topic using its creation page:
      | Title        | Behat Topic public   |
      | Description  | Testing  visibility  |
      | Type         | News                 |
      | Published    | True                 |

    When I am logged in as an "verified"
    And I am on the homepage

    Then I should see "All topics"
    And I should see "Behat Topic public"

  Scenario: Default content visibility community as VU on the homepage
    Given I set the configuration item "entity_access_by_field.settings" with key "default_visibility" to "community"
    And I am logged in as an "sitemanager"
    And I create a topic using its creation page:
      | Title        | Behat Topic public   |
      | Description  | Testing  visibility  |
      | Type         | News                 |
      | Published    | True                 |

    When I am logged in as an "verified"
    And I am on the homepage

    Then I should see "All topics"
    And I should not see "Behat Topic community"
