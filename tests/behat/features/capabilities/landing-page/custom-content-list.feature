@api @javascript
Feature: Create Landing Page and add Custom Content section
  Benefit: In order to share useful information with users
  Role: AN
  Goal/desire: I want to create dynamic content which is accessible for LU/AN

  Scenario: Successfully create Landing Page with Custom Content list of Pages

    Given I enable the module "social_content_block_landing_page"
    And I enable the module "social_page_content_block"
    And I am logged in as an "sitemanager"

    # Create Custom Content list block of Pages
    When I am on "block/add/custom_content_list"
    And I fill in "List of pages" for "Block description"
    And I fill in "Pages subtitle" for "Subtitle"
    And I select "Page" from "Type of content"
    And I press "Save"

    Then I should see "Custom content list block List of pages has been created."

    # Create Landing Page featured content section with Custom Content list of Pages
    And I am logged in as an "contentmanager"
    And pages with non-anonymous author:
      | title  | status | body | field_content_visibility |
      | Page 1 | 1      | Foo  | public                   |
      | Page 2 | 1      | Foo  | public                   |
      | Page 3 | 1      | Foo  | public                   |
    And I am on "node/add/landing_page"
    And I fill in the following:
      | Title | This is a landing page with Pages |
    And I press "Add Section"
    And I wait for AJAX to finish
    And I press "Add Custom content list block"
    And I wait for AJAX to finish
    And I fill in "List of pages" for "field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_custom_content_list][0][target_id]"
    And I press "Create landing page"
    And I wait for AJAX to finish
    And I should see "Landing page This is a landing page with Pages has been created."
    And I should see "Pages subtitle"
    And I should see "Page 1"
    And I should see "Page 2"
    And I should see "Page 3"

  Scenario: Successfully create Landing Page with Custom Content list of Book pages

    Given I enable the module "social_content_block_landing_page"
    And I enable the module "social_book_content_block"
    And I am logged in as an "sitemanager"

    # Create Custom Content list block of Book pages
    When I am on "block/add/custom_content_list"
    And I fill in "List of book pages" for "Block description"
    And I fill in "Book pages subtitle" for "Subtitle"
    And I select "Book page" from "Type of content"
    And I press "Save"

    Then I should see "Custom content list block List of book pages has been created."

    # Create Landing Page featured content section with Custom Content list of Books
    And I am logged in as an "contentmanager"
    And books:
      | title       | status | field_content_visibility |
      | Book page 1 | 1      | community                |
      | Book page 2 | 1      | community                |
      | Book page 3 | 1      | community                |
    And I am on "node/add/landing_page"
    And I fill in the following:
      | Title | This is a landing page with Books |
    And I press "Add Section"
    And I wait for AJAX to finish
    And I press "Add Custom content list block"
    And I wait for AJAX to finish
    And I fill in "List of book pages" for "field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_custom_content_list][0][target_id]"
    And I press "Create landing page"
    And I wait for AJAX to finish
    And I should see "Landing page This is a landing page with Books has been created."
    And I should see "Book pages subtitle"
    And I should see "Book page 1"
    And I should see "Book page 2"
    And I should see "Book page 3"
