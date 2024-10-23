@api 
Feature:  Validate access and visibility of events for Anonymous user and content manager

  Background:
    Given I enable the module "social_group_flexible_group"
    And events with non-anonymous author:
      | title               | body                   | field_event_date    | field_event_date_end | field_content_visibility |
      | Public event        | Body description text. | 2035-01-01T11:00:00 | 2035-01-02T18:00:00  | public                   |
      | Community event     | Body description text. | 2035-01-01T11:00:00 | 2035-01-01T18:00:00  | community                |
    #Create event with different visibility in a flexible group
    And groups with non-anonymous owner:
      | label                   | field_group_description          | field_flexible_group_visibility | field_group_allowed_visibility  |type            |
      | Flexible group for event| Description of Flexible group    | public                          | public,community,group          |flexible_group  |
    And events with non-anonymous author:
      | title                   | body                   | field_event_date    | field_event_date_end | group  | field_content_visibility      |
      | Public event in group   | Body description text. | 2035-01-01T11:00:00 | 2035-01-02T18:00:00  | Flexible group for event | public      |
      | Community event in group| Body description text. | 2035-01-01T11:00:00 | 2035-01-01T18:00:00  | Flexible group for event | community   |
      | Secret event in group   | Body description text. | 2035-01-01T11:00:00 | 2035-01-01T18:00:00  | Flexible group for event | group       |

Scenario: Anonymous user should only see public events
  Given I am an anonymous user

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

  And I open the "event" node with title "Community event in group"
  And I should not see "Community event in group"
  And I should see "Access denied"

  And I open the "event" node with title "Secret event in group"
  And I should not see "Secret event in group"
  And I should see "Access denied"
   
Scenario: Content manager should see all events
  Given I am logged in as a user with the contentmanager role

  When I am on "/community-events"

  Then I should see "Public event"
  And I should see "Community event"
  And I should see "Public event in group"
  And I should see "Community event in group"
  And I should see "Secret event in group"

  And I open the "event" node with title "Public event"
  And I should see "Public event"

  And I open the "event" node with title "Public event in group"
  And I should see "Public event in group"

  And I open the "event" node with title "Community event"
  And I should see "Community event"

  And I open the "event" node with title "Community event in group"
  And I should see "Community event in group"

  And I open the "event" node with title "Secret event in group"
  And I should see "Secret event in group"
