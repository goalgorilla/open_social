@api 
Feature: Validate access and visibility of topics for Verified User
	

  Scenario: Create topics with visibility public, community and group
  Background:
    	Given topics with non-anonymous author:
     	 | title                         | field_topic_type | status | field_content_visibility | body                         |
     	 | This is a topic for public    | Blog             | 1      | public                   | Testing public visibility    |
     	 | This is a topic for community | Blog             | 1      | community                | Testing community visibility |

  #Create topics with different visibility in a flexible group
    	Given I enable the module "social_group_flexible_group"

   	 And groups with non-anonymous owner:
    	  | label        		   | field_group_description      	| field_flexible_group_visibility | field_group_allowed_visibility  |type            |
    	  | Flexible group for topic   | Description of Flexible group	| public                          | public,community,group          |flexible_group  |

  	  And topics with non-anonymous author:
   	  | title					| body        	 | group       		   	 | field_content_visibility | field_topic_type |
    	  | This is a public topic in group		| Descriptions	 | Flexible group for topic	 | public                   | Blog  |
    	  | This is a community topic in group	| Descriptions	 | Flexible group for topic	 | community                | Blog  |
    	  | This is a secret topic in group		| Descriptions	 | Flexible group for topic	 | group                    | Blog  |
   
	And users:
     	 | name           | mail                       | status |
     	 | Group Member   | group_member@example.com    | 1      |

  #Add a user to the group
	Given I am logged in as a user with the sitemanager role
	And I am on "/all-groups"
   	And I click "Flexible group for topic"
    	And I click "Manage members"
    	And I click the group member dropdown
    	And I click "Add directly"
    	And I fill in select2 input ".form-type-select" with "Group Member" and select "Group Member"
    	And I wait for AJAX to finish
    	And I press "Save"
    	And I should see "1 new member joined the group."


  	Scenario: Verified user should see public and community topics
  	Given I am logged in as a user with the verified role
	When I am on "/all-topics"
   	Then I should see "This is a topic for public"
     	And I should see "This is a topic for community"
	And I should see "This is a public topic in group"
	And I should see "This is a community topic in group"
	And I should not see "This is a secret topic in group"
	#And I make a screenshot with the name "all-topics via VU2"  

	When I open the "topic" node with title "This is a topic for public"
	Then I should see "This is a topic for public"

	When I open the "topic" node with title "This is a public topic in group"
	Then I should see "This is a public topic in group"

	When I open the "topic" node with title "This is a topic for community"
	Then I should see "This is a topic for community"

	When I open the "topic" node with title "This is a community topic in group"
	Then I should see "This is a community topic in group"

	When I open the "topic" node with title "This is a secret topic in group"
	Then I should not see "This is a secret topic in group"
   	And I should see "Access denied"
   	And I should see "You are not authorized to access this page."


	Scenario: Verified user group member should see all topics of the group
	Given I am logged in as "Group Member"
     	When I am on "/all-topics"
   	Then I should see "This is a topic for public"
   	And I should see "This is a topic for community"
	And I should see "This is a public topic in group"
	And I should see "This is a community topic in group"
	And I should see "This is a secret topic in group"
	#And I make a screenshot with the name "all-topics via group member"


	When I open the "topic" node with title "This is a topic for public"
	Then I should see "This is a topic for public"

	When I open the "topic" node with title "This is a public topic in group"
	Then I should see "This is a public topic in group"

	When I open the "topic" node with title "This is a topic for community"
	Then I should see "This is a topic for community"

	When I open the "topic" node with title "This is a community topic in group"
	Then I should see "This is a community topic in group"

	When I open the "topic" node with title "This is a secret topic in group"
	Then I should see "This is a secret topic in group"


