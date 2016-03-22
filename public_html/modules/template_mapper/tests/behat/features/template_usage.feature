@api
  Feature: Suggest a template
    As an administrator,
    I want to provide alternate template names.

    Scenario: No template override used for articles prior to configuring module.
      Given I am logged in as a user with the "create article content" permissions
      And I visit "node/add/article"
      And I fill in "Title" with "First Article Node"
      And I press the "Save" button
      # todo, update name
      Then I should not see the text "The file node--longform-prose.html.twig from template_mapper_test_theme"
      Then I should see the text "The file node.html.twig from template_mapper_test_theme"

    Scenario: No template override used for pages prior to configuring module.
      Given I am logged in as a user with the "create page content" permissions
      And I visit "node/add/page"
      And I fill in "Title" with "Page Node"
      And I press the "Save" button
      # todo, update name
      Then I should not see the text "The file node--marketing-message.html.twig from template_mapper_test_theme"
      Then I should see the text "The file node.html.twig from template_mapper_test_theme"


    Scenario: Configure template override for node article full
      # @todo make permissions specific to this module.
      #Given I am logged in as a user with the :permissions
      #  | permissions                   |
      #  | create article content        |
      #  | administer site configuration |

      Given I am logged in as a user with the "administrator" role
      And I go to "admin/structure/template_mapping"
      And I should see the text "No template mappings have been added yet."
      And I follow "Add a new template mapping"
      And I fill in "Pre-existing theme hook" with "node__article__full"
      And I fill in "Replacement suggestion" with "node__longform_prose"
      And I press the "Save" button
      # @todo add check for the target suggestion.
      Then I should see the text "node__article__full" in the "node__longform_prose" row
      # @todo, this assertion covers the presave in TemplateMapping. That method
      # should be tested with a unit test.
      Then I should see the text "node__article__full:node__longform_prose" in the "node__longform_prose" row

      And I go to "admin/structure/template_mapping/add"
      And I fill in "Pre-existing theme hook" with "node__page__full"
      And I fill in "Replacement suggestion" with "node__marketing_message"
      And I press the "Save" button
            # @todo add check for the target suggestion.
      Then I should see the text "node__page__full" in the "node__marketing_message" row
      # @todo, this assertion covers the presave in TemplateMapping. That method
      # should be tested with a unit test.
      Then I should see the text "node__page__full:node__marketing_message" in the "node__marketing_message" row

      # verify article.
      And I visit "node/add/article"
      And I fill in "Title" with "Article Node 2"
      And I press the "Save" button
      Then I should see the text "The file node--longform-prose.html.twig from template_mapper_test_theme"
      Then I should not see the text "The file node.html.twig from template_mapper_test_theme"
      # verify page.
      And I visit "node/add/page"
      And I fill in "Title" with "Page Node 2"
      And I press the "Save" button
      Then I should see the text "The file node--marketing-message.html.twig from template_mapper_test_theme"
      Then I should not see the text "The file node.html.twig from template_mapper_test_theme"

      And I go to "admin/structure/template_mapping"
      And I click "Delete" in the "node__article__full" row
      And I press the "Delete" button
      And I go to "admin/structure/template_mapping"
      Then I should not see the text "node__article__full"

      And I go to "admin/structure/template_mapping"
      And I click "Delete" in the "node__page__full" row
      And I press the "Delete" button
      And I go to "admin/structure/template_mapping"
      Then I should not see the text "node__page__full"
