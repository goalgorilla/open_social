@api @event @stability @javascript @DS-406
Feature: Create Event
  Benefit: In order to connect with other people offline
  Role: As a LU
  Goal/desire: I want to create Events

  @LU @perfect @critical @wip
  Scenario: Successfully create event
    Given I am logged in as an "authenticated user"
    And I am on "node/add/event"
    When I fill in the following:
         | Title | This is a test event |
         | Date | 2025-01-01 |
         | Time | 11:00:00 |
         | Location name | GG HQ |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
#    And I select "NL" from "Country"
#    And I wait for AJAX to finish
#    Then I should see "City"
#    And I fill in the following:
#         | City | Enschede |
#         | Street address | Oldenzaalsestraat |
#         | Postal code | 7514DR |
    And I press "Save"
    Then I should see "This is a test event has been created."
    And I should see "THIS IS A TEST EVENT"
    And I should see "Body description text" in the "Main content"
    And I should see "Wed, 01/01/2025 - 11:00" in the "Page title block"
#    And I should see "Oldenzaalsestraat" in the "Page title block"
#    And I should see "7514DR" in the "Page title block"
#    And I should see "Enschede" in the "Page title block"
