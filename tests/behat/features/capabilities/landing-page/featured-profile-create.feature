@api @landing-page @stability @javascript @perfect @critical @DS-4130 @stability-4 @landing-page-create-featured-profile
Feature: Create Landing Page and add Featured Content section with user profile
  Benefit: In order to share useful information with users
  Role: AN
  Goal/desire: I want to create dynamic content which is accessible for LU/AN

  Scenario: Successfully create Landing Page with Featured Content section

    Given I enable the module "social_featured_content"
    And users:
      | name   | mail                     | status | field_profile_first_name | field_profile_last_name | field_profile_nick_name |
      | user_profile_1 | user_profile_1@example.localhost | 1      | Open teaser profile                   |                     |                         |

    # Create Landing Page featured content section with profiles.
    Given I am logged in as an "contentmanager"
    When I am on "node/add/landing_page"
    And I fill in the following:
      | Title | This is a dynamic page |
    And I click radio button "Public - visible to everyone including people who are not a member" with the id "edit-field-content-visibility-public"
    And I press "Add Section"
    And I wait for AJAX to finish
    And I press "Add Featured Content"
    And I wait for AJAX to finish
    And I select "profile" from "field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_featured_items][0][target_type]"
    And I fill in "user_profile_1" for "field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_featured_items][0][target_id]"
    And I wait for AJAX to finish

    # Set URL Alias
    And I click "URL path settings"
    And I set alias as "landingpage-teaser-profile"
    And I press "Save"

    # Ses as LU
    Then I should see "Landing page This is a dynamic page has been created."
    And I should see "Member"
    And I should see the link "Open teaser profile"
    And I should see the link "Read more"

    # Open stream/profile pages as LU
    When I click "Read more"
    Then I should see the link "See full profile"
    When I click "See full profile"
    Then I should see "Open teaser profile has not shared profile information."

    # See as AN
    Given I logout
    And I go to "landingpage-teaser-profile"
    Then I should see "Member"
    And I should see the link "Open teaser profile"
    And I should see the link "Read more"

    # Open stream/profile pages as AN
    When I click "Read more"
    Then I should see "Access denied. You must log in to view this page."
    When I am on "/profile/1"
    Then I should see "Access denied. You must log in to view this page."
