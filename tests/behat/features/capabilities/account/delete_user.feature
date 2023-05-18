@api @account @delete @stability @big-data @PROD-24912
Feature: Deleting a user with activities related to it
  Benefit: That we adhere to the privacy laws
  Role: LU, SM
  Goal/desire: Making sure that the user is deleted according to the chosen method and that activities are deleted with a batch process

  # Delete user with related activities.
  Scenario: Delete user as a sitemanager
    Given users:
      | name              | status | mail                         | pass         | roles       |
      | BehatUserToDelete |      1 | usertodelete@example.com     | UserToDelete | verified    |
      | BehatSiteManager  |      1 | behatsitemanager@example.com | SiteManager  | sitemanager |
    And "1" topics with title "Behat Topic [id]" by "BehatUserToDelete"
    And "1" comments with text "Behat Comment [id]" for "Behat Topic 1"
    And I wait for the queue to be empty
    And I am logged in as "BehatSiteManager"
    When I delete user "BehatUserToDelete" with method "user_cancel_delete"
    Then I should see log message "Number of activities deleted by batch: 1"
