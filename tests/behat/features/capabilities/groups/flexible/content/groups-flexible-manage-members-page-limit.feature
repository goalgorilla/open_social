@api @javascript
Feature: Items per page limit on the Manage members overview page

  Background:
    Given I enable the module "social_group_flexible_group"

    And groups with non-anonymous owner:
      | label           | field_group_description      | field_flexible_group_visibility | field_group_allowed_visibility  |type            |
      | Flexible group  | Description of Flexible group| public                          | public                          |flexible_group  |
    And users:
      | name    | mail                     | status | roles    |
      | member1   | mail_user1@example.com | 1      | verified |
      | member2   | mail_user2@example.com | 1      | verified |
      | member3   | mail_user3@example.com | 1      | verified |
      | member4   | mail_user4@example.com | 1      | verified |
      | member5   | mail_user5@example.com | 1      | verified |
      | member6   | mail_user6@example.com | 1      | verified |
      | member7   | mail_user7@example.com | 1      | verified |
      | member8   | mail_user8@example.com | 1      | verified |
      | member9   | mail_user9@example.com | 1      | verified |
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
    And group members:
      |  group          | user    |
      | Flexible group  | member1 |
      | Flexible group  | member2 |
      | Flexible group  | member3 |
      | Flexible group  | member4 |
      | Flexible group  | member5 |
      | Flexible group  | member6 |
      | Flexible group  | member7 |
      | Flexible group  | member8 |
      | Flexible group  | member9 |
      | Flexible group  | member10 |
      | Flexible group  | member11 |
      | Flexible group  | member12 |
      | Flexible group  | member13 |
      | Flexible group  | member14 |
      | Flexible group  | member15 |
      | Flexible group  | member16 |
      | Flexible group  | member17 |
      | Flexible group  | member18 |
      | Flexible group  | member19 |
      | Flexible group  | member20 |
      | Flexible group  | member21 |
      | Flexible group  | member22 |
      | Flexible group  | member23 |
      | Flexible group  | member24 |
      | Flexible group  | member25 |
      | Flexible group  | member26 |

  Scenario: User can control the number of items displayed on the Manage members overview page

    Given I am logged in as a user with the sitemanager role

    When I am viewing the group "Flexible group"

    And I click "Manage members"
    And I should see "27 members"
    And I select "50" from "items_per_page"

    # Should be displayed all items without page.
    Then I should not see the link "Next"
    And I should not see the link "Last"

    # Check that selected number of items now is permanent for the current user.
    And I click "About"
    And I click "Manage members"
    And should see "50" selected in the "items_per_page" select field
    And I should not see the link "Next"
    And I should not see the link "Last"
