@api @landing-page @stability @javascript @perfect @critical @DS-4130 @stability-4 @landing-page-create
Feature: Create Landing Page
  Benefit: In order to share useful information with users
  Role: AN
  Goal/desire: I want to create dynamic content on the site

  Scenario: Successfully create Landing Page

    Given I enable the module "social_landing_page"
    Given event content:
      | title          | field_event_date | status | field_content_visibility |
      | Featured Event | +10 minutes      | 1      | public                   |
    Given "topic_types" terms:
      | name                  |
      | News                  |
      | Blog                  |
    Given topic content:
      | title            | field_topic_type | status | field_content_visibility |
      | Featured Topic 1 | News             | 1      | public                   |
      | Featured Topic 2 | Blog             | 1      | public                   |
    # Create Landing Page Hero
    Given I am logged in as an "contentmanager"
    When I am on "node/add/landing_page"
    And I fill in the following:
      | Title | This is a dynamic page |
    And I click radio button "Public" with the id "edit-field-content-visibility-public"
    And I press "Add Section"
    And I wait for AJAX to finish
    And I press "Add Hero"
    And I wait for AJAX to finish
    And I fill in the following:
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_title][0][value]                                     | Hero title    |
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_subtitle][0][value]                                  | Hero subtitle |
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_buttons][0][subform][field_button_link_an][0][title] | Hero Link AN  |
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_buttons][0][subform][field_button_link_an][0][uri]   | /sign-up      |
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_buttons][0][subform][field_button_link_lu][0][title] | Hero Link LU  |
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_buttons][0][subform][field_button_link_lu][0][uri]   | /all-members  |
    And I press "Add Button"
    And I wait for AJAX to finish
    And I fill in the following:
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_buttons][1][subform][field_button_link_an][0][title] | Hero Link AN 2 |
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_buttons][1][subform][field_button_link_an][0][uri]   | /log-in        |
    And I select "btn-primary" from "field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_buttons][1][subform][field_button_style]"
    And I press "Add Section"
    And I wait for AJAX to finish
    # Create Introduction
    And I press "Add Introduction"
    And I wait for AJAX to finish
    And I fill in the following:
      | field_landing_page_section[1][subform][field_section_paragraph][0][subform][field_introduction_title][0][value]   | Introduction title             |
      | field_landing_page_section[1][subform][field_section_paragraph][0][subform][field_introduction_link_an][0][uri]   | /log-in                        |
      | field_landing_page_section[1][subform][field_section_paragraph][0][subform][field_introduction_link_an][0][title] | Introduction Link AN           |
      | field_landing_page_section[1][subform][field_section_paragraph][0][subform][field_introduction_link_lu][0][uri]   | https://www.getopensocial.com/ |
      | field_landing_page_section[1][subform][field_section_paragraph][0][subform][field_introduction_link_lu][0][title] | Introduction Link LU           |
    And I press "Add Section"
    And I wait for AJAX to finish
    # Create Featured
    And I press "Add Featured"
    And I wait for AJAX to finish
    And I fill in the following:
      | field_landing_page_section[2][subform][field_section_paragraph][0][subform][field_featured_title][0][value] | Featured title  |
      | field_landing_page_section[2][subform][field_section_paragraph][0][subform][field_featured_link][0][uri]    | /search/content |
      | field_landing_page_section[2][subform][field_section_paragraph][0][subform][field_featured_link][0][title]  | Featured Link   |
    And I fill in "field_landing_page_section[2][subform][field_section_paragraph][0][subform][field_featured_items][0][target_id]" with "Featured Event"
    And I press "Add another item"
    And I wait for AJAX to finish
    And I fill in "field_landing_page_section[2][subform][field_section_paragraph][0][subform][field_featured_items][1][target_id]" with "Featured Topic 1"
    And I press "Add another item"
    And I wait for AJAX to finish
    And I fill in "field_landing_page_section[2][subform][field_section_paragraph][0][subform][field_featured_items][2][target_id]" with "Featured Topic 2"
    And I press "Add Section"
    And I wait for AJAX to finish
    # Create Block
    And I press "Add Block"
    And I wait for AJAX to finish
    And I select "views_block:community_activities-block_stream_landing" from "field_landing_page_section[3][subform][field_section_paragraph][0][subform][field_block_reference][0][plugin_id]"
    And I select "activity_overview_block" from "field_landing_page_section[3][subform][field_section_paragraph][0][subform][field_block_reference_secondary][0][plugin_id]"
    And I fill in the following:
      | field_landing_page_section[3][subform][field_section_paragraph][0][subform][field_block_link][0][uri]   | /explore   |
      | field_landing_page_section[3][subform][field_section_paragraph][0][subform][field_block_link][0][title] | Block Link |
    # Set URL Alias
    And I click "URL path settings"
    And I set alias as "landingpage"
    And I press "Save"
    # Ses as LU
    Then I should see "Landing page This is a dynamic page has been created."
    And I should see "Hero title"
    And I should see "Hero subtitle"
    And I should see the link "Hero Link LU"
    And I should not see the link "Hero Link AN"
    And I should not see the link "Hero Link AN 2"
    And I should see "Introduction title"
    And I should see the link "Introduction Link LU"
    And I should not see the link "Introduction Link AN"
    And I should see "Featured title"
    And I should see the link "Featured Link"
    And I should see the link "Featured Event"
    And I should see the link "Featured Topic 1"
    And I should see the link "Featured Topic 2"
    And I should see "Community activities"
    # Quick edit
    Given I click "Edit content"
    Then I should see "Hero title"
    And I should see "Hero subtitle"
    And I should see the link "Hero Link LU"
    And I should see "Introduction title"
    And I should see the link "Introduction Link LU"
    And I should see "Featured title"
    And I should see the link "Featured Link"
    And I should see the link "Featured Event"
    And I should see the link "Featured Topic 1"
    And I should see the link "Featured Topic 2"
    And I should see "Community activities"
    When I press "field_landing_page_section_0_edit"
    And I wait for AJAX to finish
    And I fill in the following:
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_title][0][value] | Hero title edited |
    And I press "Save"
    Then I should see "Landing page This is a dynamic page has been updated."
    # See as AN
    Given I logout
    And I go to "landingpage"
    Then I should see "Hero title" in the "Main content"
    And I should see "Hero subtitle" in the "Main content"
    And I should not see the link "Hero Link LU"
    And I should see the link "Hero Link AN"
    And I should see the link "Hero Link AN 2"
    And I should see "Introduction title"
    And I should not see the link "Introduction Link LU"
    And I should see the link "Introduction Link AN"
    And I should see "Featured title"
    And I should see the link "Featured Link"
    And I should see the link "Featured Event"
    And I should see the link "Featured Topic 1"
    And I should see the link "Featured Topic 2"
    And I should see "Community activities"
