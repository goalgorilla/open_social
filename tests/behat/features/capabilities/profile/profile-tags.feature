@api
Feature:  Profile tagging
  Depending on the profile field configuration users and/or site managers are able to
  add tags to profiles in fields configured through a site-manager defined taxonomy hierarchy.

  Background:
    Given users:
      | name   | status | roles    |
      | Member | 1      | verified |
    And the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | Profile tag        | false               | Community  | false                    | false                           | false                         | true                             | false                          | false                | false    |

  Scenario: Enable profile tags with a single field
    Given "profile_tag" terms:
      | name        | parent      |
      | Profile tag |             |
      | Behat tag 1 | Profile tag |
      | Behat tag 2 | Profile tag |

    When I am logged in as an "contentmanager"
    And I try to edit the profile of "Member"
    And I select "Behat tag 1" from "Profile tag"
    And I additionally select "Behat tag 2" from "Profile tag"
    And I press "Save"

    Then I should see "The profile has been saved"
    And I should see "Profile tag"
    And I should see "Behat tag 1"
    And I should see "Behat tag 2"

  Scenario: Enable profile tag split
    Given "profile_tag" terms:
      | name          | parent      |
      | Behat tag 1   |             |
      | Behat tag 1.1 | Behat tag 1 |
      | Behat tag 1.2 | Behat tag 1 |
      | Behat tag 2   |             |
      | Behat tag 2.1 | Behat tag 2 |
      | Behat tag 2.2 | Behat tag 2 |

    When I am logged in as an "contentmanager"
    And I try to edit the profile of "Member"
    And I select "Behat tag 1.1" from "Behat tag 1"
    And I additionally select "Behat tag 1.2" from "Behat tag 1"
    And I select "Behat tag 2.1" from "Behat tag 2"
    And I additionally select "Behat tag 2.2" from "Behat tag 2"
    And I press "Save"

    Then I should see "The profile has been saved"
    And I should not see "Profile tag"
    And I should see "Behat tag 1"
    And I should see "Behat tag 1.1"
    And I should see "Behat tag 1.2"
    And I should see "Behat tag 2"
    And I should see "Behat tag 2.1"
    And I should see "Behat tag 2.2"
