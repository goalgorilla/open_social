@api @topic @stability @javascript @perfect @critical @DS-486 @stability-3 @unpublished
Feature: Un/publish a node
  Benefit: In order to make drafts
  Role: as a Verified
  Goal/desire: I want to un/publish

  Scenario: Successfully create unpublished topic
    Given I am logged in as an "verified"

    When I create a topic using its creation page:
      | Title        | This is a test topic   |
      | Description  | Body description text  |
      | Type         | News                   |
      | Published    | False                  |

    Then I should see the topic I just created

  Scenario: Successfully publish an unpublished topic
    Given I am logged in as an "verified"
    And topics authored by current user:
      | title    | body            | field_content_visibility | field_topic_type | langcode    | status |
      | My title | My description  | public                   | News             | en          | 0         |

    When I edit topic "My title" using its edit page:
      | Published    | True                   |

    Then I should see the topic I just updated

  Scenario: Normal user only sees published topics
    Given topics with non-anonymous author:
      | title                     | body            | field_content_visibility | field_topic_type | langcode    | status |
      | This topic is published   | My description  | public                   | News             | en          | 1         |
      | This topic is unpublished | My description  | public                   | News             | en          | 0         |
    And I am logged in as a user with the "authenticated" role

    When I am on the topic overview

    Then I should see "This topic is published"
    And I should not see "This topic is unpublished"
