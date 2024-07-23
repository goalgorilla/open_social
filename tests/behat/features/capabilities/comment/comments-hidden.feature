@api
Feature: Comments settings
	As a manager I want to hide comments
	on a topic
	so that users can't see the comment on the content

  Scenario: I add a comment on a topic
    Given I am logged in as a user with the contentmanager role
    When I go to "/node/add/topic"
    And I fill in "Title" with "Topic with comments"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Topic description"
    And I check the box "News"
    And I press "Create topic"
    And I should see "Topic Topic with comments has been created."
	  And I should see "Topic with comments" in the "Hero block"
   	And I should see "Topic description" in the "Main content"
	  And I fill in the following:
        | Add a comment | This is a test comment |
    And I press "Comment"
      Then I should see the success message "Your comment has been posted."
      And I should see the heading "Comments (1)" in the "Main content"
      And I should see "This is a test comment" in the "Main content"
  And I make a screenshot with the name "open comment"

  #Scenario: I hide comments on the topic
	When I am editing the topic "Topic with comments"
	Then I fill in "Title" with "Topic with hidden comments"
   	And I click radio button "Hidden"
	  And I press "Save"
    	Then I should see "Topic Topic with hidden comments has been updated."
	    And I should see "Topic with hidden comments" in the "Hero block"
 	    And I should not see "This is a test comment" in the "Main content"
  #And I make a screenshot with the name "hidden comment"
