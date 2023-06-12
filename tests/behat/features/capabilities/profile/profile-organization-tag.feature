@api @profile @user
Feature: Check that profile organization tags are correctly displayed
  Check that profile organization tags are corectly displayed also with special characters like & or '.

  Scenario: Successfuly show profile organization tag on profile and stream card
    Given I enable the module "social_profile_organization_tag"
    And "profile_organization_tag" terms:
      | tid | name  |
      | 999 | A&B's |
    And users:
      | name              | mail                          | status | field_profile_first_name | field_profile_last_name  | field_profile_organization_tag | roles       |
      | behat_sitemanager | behat_sitemanager@example.com | 1      | Behat                    | Sitemanager              | 999                            | sitemanager |
    And I am logged in as "behat_sitemanager"
    And "1" topics with title "Behat Topic [id]" by "behat_sitemanager"
    And "1" comments with text "Behat Comment [id]" for "Behat Topic 1"
    # This should automaticly redirect to \user\[id]\stream
    When I am on "\user"
    Then I should see the text "Behat Sitemanager from A&B's commented on Behat Sitemanager's topic"
    And I should see "A&B's" in the ".profile-organization-tag .text" element
