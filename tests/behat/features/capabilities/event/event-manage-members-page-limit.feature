@api @javascript
Feature: Items per page limit on the Manage members overview page

  Background:
    Given I enable the module "social_event_managers"

    And users:
      | name       | mail                    | status | roles    |
      | member1    | mail_user1@example.com  | 1      | verified |
      | member2    | mail_user2@example.com  | 1      | verified |
      | member3    | mail_user3@example.com  | 1      | verified |
      | member4    | mail_user4@example.com  | 1      | verified |
      | member5    | mail_user5@example.com  | 1      | verified |
      | member6    | mail_user6@example.com  | 1      | verified |
      | member7    | mail_user7@example.com  | 1      | verified |
      | member8    | mail_user8@example.com  | 1      | verified |
      | member9    | mail_user9@example.com  | 1      | verified |
      | member10   | mail_user10@example.com | 1      | verified |
      | member11   | mail_user11@example.com | 1      | verified |
      | member12   | mail_user12@example.com | 1      | verified |
      | member13   | mail_user13@example.com | 1      | verified |
      | member14   | mail_user14@example.com | 1      | verified |
      | member15   | mail_user15@example.com | 1      | verified |
      | member16   | mail_user16@example.com | 1      | verified |
      | member17   | mail_user17@example.com | 1      | verified |
      | member18   | mail_user18@example.com | 1      | verified |
      | member19   | mail_user19@example.com | 1      | verified |
      | member20   | mail_user20@example.com | 1      | verified |
      | member21   | mail_user21@example.com | 1      | verified |
      | member22   | mail_user22@example.com | 1      | verified |
      | member23   | mail_user23@example.com | 1      | verified |
      | member24   | mail_user24@example.com | 1      | verified |
      | member25   | mail_user25@example.com | 1      | verified |
      | member26   | mail_user26@example.com | 1      | verified |
    And event content:
      | title         | field_event_date | status | field_content_visibility | field_event_an_enroll | author  |
      | Test Event    | +2 days          | 1      | public                   | 1                     | member1 |
    And event enrollees:
      | event       | user     |
      | Test Event  | member1  |
      | Test Event  | member2  |
      | Test Event  | member3  |
      | Test Event  | member4  |
      | Test Event  | member5  |
      | Test Event  | member6  |
      | Test Event  | member7  |
      | Test Event  | member8  |
      | Test Event  | member9  |
      | Test Event  | member10 |
      | Test Event  | member11 |
      | Test Event  | member12 |
      | Test Event  | member13 |
      | Test Event  | member14 |
      | Test Event  | member15 |
      | Test Event  | member16 |
      | Test Event  | member17 |
      | Test Event  | member18 |
      | Test Event  | member19 |
      | Test Event  | member20 |
      | Test Event  | member21 |
      | Test Event  | member22 |
      | Test Event  | member23 |
      | Test Event  | member24 |
      | Test Event  | member25 |
      | Test Event  | member26 |

  Scenario: User can control the number of items displayed on the Manage members overview page
    Given I am logged in as a user with the sitemanager role

    When I am viewing the event "Test Event"

    And I click "Manage enrollments"
    And I should see "26 enrollees"
    And I select "50" from "items_per_page"

    # Should be displayed all items without page.
    Then I should not see the link "Next"
    And I should not see the link "Last"