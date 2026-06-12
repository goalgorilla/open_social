@api
Feature: Validate topic comment visibility by comment status

  Scenario Outline: Users cannot view or add comments on topics with hidden comments
    Given topics with non-anonymous author:
      | title                       | body       | field_content_visibility | field_topic_type | status |
      | Topic with hidden comments  | Topic body | community                | News             | 1      |
    And topic with "Topic with hidden comments" have "hidden" comments
    And comments with non-anonymous author:
      | target_type | target_label               | status | subject                | field_comment_body                  | comment_type |
      | node:topic  | Topic with hidden comments | 1      | Hidden comment subject | Hidden comments should not be seen. | comment      |
    And I am logged in as a user with the <role> role

    When I open the "topic" node with title "Topic with hidden comments"

    Then I should see "Topic with hidden comments"
    And I should not see "Hidden comments should not be seen."
    And I should not see a field labeled "Add a comment"

    Examples:
      | role          |
      | verified      |
      | authenticated |

  Scenario Outline: Users with elevated permissions can view comments on topics with hidden comments
    Given topics with non-anonymous author:
      | title                       | body       | field_content_visibility | field_topic_type | status |
      | Topic with hidden comments  | Topic body | community                | News             | 1      |
    And topic with "Topic with hidden comments" have "hidden" comments
    And comments with non-anonymous author:
      | target_type | target_label               | status | subject                | field_comment_body                  | comment_type |
      | node:topic  | Topic with hidden comments | 1      | Hidden comment subject | Hidden comments should not be seen. | comment      |
    And I am logged in as a user with the <role> role

    When I open the "topic" node with title "Topic with hidden comments"

    Then I should see "Topic with hidden comments"
    And I should see "Hidden comments should not be seen."
    And I should not see a field labeled "Add a comment"

    Examples:
      | role           |
      | sitemanager    |
      | contentmanager |
