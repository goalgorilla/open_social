@api @landing-page @stability @javascript @perfect @YANG-4945 @stability-4 @landing-page-create-custom-content-list
Feature: Create Landing Page and add Custom Content section
  Benefit: In order to share useful information with users
  Role: AN
  Goal/desire: I want to create dynamic content which is accessible for LU/AN

  Scenario: Successfully create Landing Page with Custom Content list of Basic pages

    Given I enable the module "social_content_block_landing_page"
    Given I enable the module "social_page_content_block"

    # Create Custom Content list block of Basic pages
    Given I am logged in as an "sitemanager"
    When I am on "block/add/custom_content_list"
    And I fill in "List of basic pages" for "Block description"
    And I fill in "Basic pages subtitle" for "Subtitle"
    And I select "Basic page" from "Type of content"
    And I press "Save"
    Then I should see "Custom content list block List of basic pages has been created."

    # Create Landing Page featured content section with Custom Content list of Pages
    Given I am logged in as an "contentmanager"
    Given page content:
      | title        | status |
      | Basic page 1 | 1      |
      | Basic page 2 | 1      |
      | Basic page 3 | 1      |

    When I am on "node/add/landing_page"
    And I fill in the following:
      | Title | This is a landing page with Pages |
    And I press "Add Section"
    And I wait for AJAX to finish
    And I press "Add Custom content list block"
    And I wait for AJAX to finish
    And I fill in "List of basic pages" for "field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_custom_content_list][0][target_id]"
    And I press "Create landing page"
    And I wait for AJAX to finish
    Then I should see "Landing page This is a landing page with Pages has been created."
    Then I should see "Basic pages subtitle"
    Then I should see "Basic page 1"
    Then I should see "Basic page 2"
    Then I should see "Basic page 3"

  Scenario: Successfully create Landing Page with Custom Content list of Book pages

    Given I enable the module "social_content_block_landing_page"
    Given I enable the module "social_book_content_block"

    # Create Custom Content list block of Book pages
    Given I am logged in as an "sitemanager"
    When I am on "block/add/custom_content_list"
    And I fill in "List of book pages" for "Block description"
    And I fill in "Book pages subtitle" for "Subtitle"
    And I select "Book page" from "Type of content"
    And I press "Save"
    Then I should see "Custom content list block List of book pages has been created."

    # Create Landing Page featured content section with Custom Content list of Books
    Given I am logged in as an "contentmanager"
    Given book content:
      | title       | status |
      | Book page 1 | 1      |
      | Book page 2 | 1      |
      | Book page 3 | 1      |

    When I am on "node/add/landing_page"
    And I fill in the following:
      | Title | This is a landing page with Books |
    And I press "Add Section"
    And I wait for AJAX to finish
    And I press "Add Custom content list block"
    And I wait for AJAX to finish
    And I fill in "List of book pages" for "field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_custom_content_list][0][target_id]"
    And I press "Create landing page"
    And I wait for AJAX to finish
    Then I should see "Landing page This is a landing page with Books has been created."
    Then I should see "Book pages subtitle"
    Then I should see "Book page 1"
    Then I should see "Book page 2"
    Then I should see "Book page 3"
