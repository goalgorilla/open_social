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
    # Thorough testing would require creating a test with a high number of activities
    # to simulate real-world scenarios that would cause out of memory error without batch processing.
    # Currently, we verify the need for batch processing by checking the logs that are
    # triggered at the end of the batch process. However, this approach may change
    # in the future as the when CI environment may become more like a production environment.
    Then I should see log message "Number of activities deleted by batch: 1"
