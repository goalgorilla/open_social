@api 
Feature:  Validate access and visibility of events for Anonymous user and content manager

  Scenario: Create events with visibility public, community and group
  Background:
    	Given events with non-anonymous author:
      | title                   | body                   | field_event_date    | field_event_date_end | field_content_visibility |
      | Public event  | Body description text. | 2035-01-01T11:00:00 | 2035-01-02T18:00:00  | public                   |
      | Community event   | Body description text. | 2035-01-01T11:00:00 | 2035-01-01T18:00:00  | community                   |

  #Create event with different visibility in a flexible group
    	Given I enable the module "social_group_flexible_group"

   	 And groups with non-anonymous owner:
    	  | label        		   | field_group_description      	| field_flexible_group_visibility | field_group_allowed_visibility  |type            |
    	  | Flexible group for event   | Description of Flexible group	| public                          | public,community,group          |flexible_group  |

  	 Given events with non-anonymous author:
      | title                   | body                   | field_event_date    | field_event_date_end | group  | field_content_visibility |
      | Public event in group  | Body description text. | 2035-01-01T11:00:00 | 2035-01-02T18:00:00  | Flexible group for event | public                   |
      | Community event in group  | Body description text. | 2035-01-01T11:00:00 | 2035-01-01T18:00:00  | Flexible group for event | community                   |
	|Secret event in group   | Body description text. | 2035-01-01T11:00:00 | 2035-01-01T18:00:00  | Flexible group for event | group                   |

	And users:
     	 | name           | mail                       | status |
     	 | Group Member   | group_member@example.com    | 1      |

  #Add a user to the group
	Given I am logged in as a user with the sitemanager role
	And I am on "/all-groups"
   	And I click "Flexible group for event"
    	And I click "Manage members"
    	And I click the group member dropdown
    	And I click "Add directly"
    	And I fill in select2 input ".form-type-select" with "Group Member" and select "Group Member"
    	And I wait for AJAX to finish
    	And I press "Save"
    	And I should see "1 new member joined the group."


	Scenario: Anonymous user should only see public events 
    	Given I am an anonymous user
    	When I am on "/community-events"
    	Then I should see "Public event"
   	And I should not see "Community event"
	And I should see "Public event in group"
	And I should not see "Community event in group"
	And I should not see "Secret event in group"
	#And I make a screenshot with the name "all-events via AN" 

	
	When I open the "event" node with title "Public event"
	Then I should see "Public event"

	When I open the "event" node with title "Public event in group"
	Then I should see "Public event in group"
	
	When I open the "event" node with title "Community event"
	Then I should not see "Community event"
    	And I should see "Access denied"
  
	When I open the "event" node with title "Community event in group"
	Then I should not see "Community event in group"
    	And I should see "Access denied"

	When I open the "event" node with title "Secret event in group"
	Then I should not see "Secret event in group"
    	And I should see "Access denied"
   

	Scenario: Content manager should see all events
    	Given I am logged in as a user with the contentmanager role
	When I am on "/community-events"
    	Then I should see "Public event"
   	And I should see "Community event"
	And I should see "Public event in group"
	And I should see "Community event in group"
	And I should see "Secret event in group"
	#And I make a screenshot with the name "all-events via CM" 

	When I open the "event" node with title "Public event"
	Then I should see "Public event"

	When I open the "event" node with title "Public event in group"
	Then I should see "Public event in group"
	
	When I open the "event" node with title "Community event"
	Then I should see "Community event"

	When I open the "event" node with title "Community event in group"
	Then I should see "Community event in group"
	
	When I open the "event" node with title "Secret event in group"
	Then I should see "Secret event in group"

