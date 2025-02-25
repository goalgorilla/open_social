@api
Feature: I want to be able to make nick names unique
  Benefit: Increased distinguishability
  Role: SM
  Goal/desire: So I can see who's who even when they have nick names

  Scenario: Nick names should be unique
    Given I enable the module "social_profile_fields"
    And users:
      | name           | mail                    | status |
      | peter_schwartz | peter@example.localhost | 1      |
      | laura_messing  | laura@example.localhost | 1      |

    And I am logged in as an "administrator"
    And I am on "admin/config/opensocial/profile-fields"
    And I check the box "Nickname"
    And I check the box "Unique nicknames"

    And I press "Save configuration"
    And I am logged in as "peter_schwartz"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Edit profile"

    And I fill in "Nickname" with "Susan"
    And I press "Save"
    And I should see "Susan"

    And I am logged in as "laura_messing"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Edit profile"

    And I fill in "Nickname" with "Susan"
    And I press "Save"
    And I should see the error message "This name is already taken, please try another one."

    And I fill in "Nickname" with "Carl"
    And I press "Save"
    And I should see "Carl"

  Scenario: Nick names do not have to be unique
    Given I enable the module "social_profile_fields"
    And users:
      | name           | mail                    | status |
      | peter_schwartz | peter@example.localhost | 1      |
      | laura_messing  | laura@example.localhost | 1      |

    And I am logged in as an "administrator"
    And I am on "admin/config/opensocial/profile-fields"
    And I check the box "Nickname"
    And I uncheck the box "Unique nicknames"
    And I press "Save configuration"

    And I am logged in as "peter_schwartz"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Edit profile"

    And I fill in "Nickname" with "Susan"
    And I press "Save"
    And I should see "Susan"

    And I am logged in as "laura_messing"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Edit profile"

    And I fill in "Nickname" with "Susan"
    And I press "Save"
    And I should see "Susan"
