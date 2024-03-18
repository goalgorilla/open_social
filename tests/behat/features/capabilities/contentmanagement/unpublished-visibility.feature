@api @topic @stability @perfect @critical @YANG-5682 @stability-3 @unpublished
Feature: Un/publish a node
  Benefit: Visibility do not have an impact on default permission
  Role: as AN/Verified
  Goal/desire: AN/Verified should not hav access to unpublished content

  @public
  Scenario: Unsuccessfully get access to unpublished content as AN
    Given I am logged in as an "contentmanager"
    And I am on "node/add/topic"
    When I fill in "Title" with "Unpublished topic"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I check the box "News"
    And I click radio button "Public"
    And I click the element with css selector "#edit-group-settings .card__title"
    And I set alias as "unpublished-topic"
    Then I should see "Publish status"
    And I should see "Published"
    And I uncheck the box "Published"
    And I press "Create topic"
    Then I should see "Unpublished topic has been created."
    Given I disable that the registered users to be verified immediately
    When I am logged in as an "authenticated user"
    And I go to "unpublished-topic"
    Then I should see "Access denied"
    And I enable that the registered users to be verified immediately
    When I logout
    And I go to "unpublished-topic"
    Then I should see "Access denied. You must log in to view this page."

  @community
  Scenario: Unsuccessfully get access to unpublished content as Verified
    Given I am logged in as an "contentmanager"
    And I am on "node/add/topic"
    When I fill in "Title" with "Unpublished topic"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I check the box "News"
    And I click the element with css selector "#edit-group-settings .card__title"
    And I set alias as "unpublished-topic"
    Then I should see "Publish status"
    And I should see "Published"
    And I uncheck the box "Published"
    And I press "Create topic"
    Then I should see "Unpublished topic has been created."
    When I am logged in as an "verified"
    And I go to "unpublished-topic"
    Then I should see "Access denied"
    Then I should see "You are not authorized to access this page."
    Given I disable that the registered users to be verified immediately
    When I am logged in as an "authenticated user"
    And I go to "unpublished-topic"
    Then I should see "Access denied"
    And I should see "You are not authorized to access this page."
    And I enable that the registered users to be verified immediately
