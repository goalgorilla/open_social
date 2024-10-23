@api 
Feature:  Validate access and visibility of events for Authenticated User (AU)

  Background:
    Given events with non-anonymous author:
      | title             | body                   | field_event_date    | field_event_date_end | field_content_visibility |
      | Public event      | Body description text. | 2035-01-01T11:00:00 | 2035-01-02T18:00:00  | public                   |
      | Community event   | Body description text. | 2035-01-01T11:00:00 | 2035-01-01T18:00:00  | community                |
    And I enable the module "social_group_flexible_group"
    And groups with non-anonymous owner:
      | label                      | field_group_description          | field_flexible_group_visibility | field_group_allowed_visibility  |type            |
      | Flexible group for event   | Description of Flexible group    | public                          | public,community,group          |flexible_group  |
    And events with non-anonymous author:
      | title                   | body                   | field_event_date    | field_event_date_end | group  | field_content_visibility    |
      | Public event in group   | Body description text. | 2035-01-01T11:00:00 | 2035-01-02T18:00:00  | Flexible group for event | public    |
      | Community event in group| Body description text. | 2035-01-01T11:00:00 | 2035-01-01T18:00:00  | Flexible group for event | community |
      |Secret event in group    | Body description text. | 2035-01-01T11:00:00 | 2035-01-01T18:00:00  | Flexible group for event | group     |

  Scenario: Unverified user should only see public events 
    Given I disable that the registered users to be verified immediately
    And I am logged in as an "authenticated user"
      
    When I am on "/community-events"
      
    Then I should see "Public event"
    And I should not see "Community event"
    And I should see "Public event in group"
    And I should not see "Community event in group"
    And I should not see "Secret event in group"

    And I open the "event" node with title "Public event"
    And I should see "Public event"

    And I open the "event" node with title "Public event in group"
    And I should see "Public event in group"

    And I open the "event" node with title "Community event"
    And I should not see "Community event"
    And I should see "Access denied"
    And I should see "You are not authorized to access this page."

    And I open the "event" node with title "Community event in group"
    And I should not see "Community event in group"
    And I should see "Access denied"
    And I should see "You are not authorized to access this page."
    
    And I open the "event" node with title "Secret event in group"
    And I should not see "Secret event in group"
    And I should see "Access denied"
    And I should see "You are not authorized to access this page."
