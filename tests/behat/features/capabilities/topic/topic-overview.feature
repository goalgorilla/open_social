@api @topic @stability @overview @DS-357 @DS-358
Feature: Topic Overview
  Benefit: In order to find a Topic from a author
  Role: As a User
  Goal/desire: I want to see an Topic overview

  @perfect @critical
  Scenario: Successfully see the topic overview
    Given I am logged in as an "authenticated user"
    And I am on "user"
    When I click "Topics"
     Then I should see "Topics" in the "Page title block"
    And I should see "Filter" in the "Sidebar second"
    And I should see text matching "is the type of"
    And I should see text matching "has the publish status of"

  # Scenario: Successfully see the topic overview of another user
    Given I am on "user/1"
    When I click "Topics"
    Then I should see "Topics" in the "Page title block"
    And I should see "Filter" in the "Sidebar second"
    And I should not see text matching "has the publish status of"

#  Scenario: Successfully filter the topic overview
#    Given I am logged in as an "authenticated user"
#    And I am on "user"
#    When I click "Topics"
#    Then I should see the heading "Topics"
#    And I should see the heading "I want to see topics that" in the "Sidebar second"
#    And I should see text matching "is the type of"
#    And I should see text matching "Sorted by publish date"
#    And I should see text matching "has the publish status of"
