@api
Feature: Follow Users
  Benefit: Provide follow users
  Role: As LU
  Goal/Desire: I want to follow users

  Scenario: Successfully follow users
    Given I enable the module "social_follow_user"
    And I set the configuration item "social_follow_user.settings" with key "status" to "true"
    And I set the configuration item "socialblue.settings" with key "style" to "sky"
    And users:
      | name              | mail                      | status | roles          | field_profile_first_name | field_profile_last_name |
      | follower          | follower@test.user        | 1      | verified       | Mike                     | Tyson                   |
      | following         | following@test.user       | 1      | verified       | Jack                     | Richer                  |
      | disable_follow    | disable_follow@test.user  | 1      | verified       | Mark                     | Twain                   |
      | behat_manager     | behat_manager@test.user   | 1      | sitemanager    | behat                    | manager                 |

    # Verify that user follow feat is active.
    And I am logged in as "behat_manager"
    And I go to "admin/config/opensocial/follow-user"
    And I should see checked the box "Active"

    # Check follow functionality on the user page.
    And I am logged in as "following"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    And I should see the text "0 followers"
    And I should see the text "0 following"

    # Check follow on the all-members page.
    And I go to "all-members"
    And I should see the link "Mike Tyson"
    And I should see the link "Follow"
    And I click "Mike Tyson"

    # Check follow ability on the profile page in statistic bock.
    And I should see "Mike Tyson" in the "#block-socialblue-profile-statistic-block" element
    And I should see the link "Follow"
    And I click "Follow"
    And I wait for AJAX to finish
    And I should see the text "Unfollow"

    # Find "disable_follow" user on the all-members page.
    And I go to "all-members"
    And I should see the link "Mark Twain"
    And I click "Mark Twain"

    # Prepare a step when user should have an access "unfollow" button.
    And I should see "Mark Twain" in the "#block-socialblue-profile-statistic-block" element
    And I should see the link "Follow"
    And I click "Follow"
    And I wait for AJAX to finish
    And I should see the text "Unfollow"

    # Check if follow counter has been update on the user page.
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    And I should see the text "0 followers"
    And I should see the text "2 following"

    # Check if followers page is accessible.
    And I click "0 followers"
    And I should see the text "This user does not have any followers"

    # Check if following page is accessible as well.
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    And I should see the text "2 following"
    And I click "2 following"

    And I should see the text "Mike Tyson"
    And I should see the text "following"
    And I should see the text "Unfollow"

    And I should see the text "Mark Twain"
    And I should see the text "following"
    And I should see the text "Unfollow"

    # Check if there is followers.
    And I am logged in as "follower"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    And I should see the text "1 follower"
    And I should see the text "0 following"
    And I click "1 follower"
    And I should see the text "Jack Richer"

    # Check a case when following is disabled.
    And I am logged in as "disable_follow"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I should see checked the box "Allow members to follow me"

    # Disable user following settings.
    And I uncheck the box "Allow members to follow me"
    And I press "Save"
    And I should see "The changes have been saved."

    # Check if following really disabled.
    And I am logged in as "follower"
    And I go to "all-members"
    And I should see the link "Mark Twain"
    And I click "Mark Twain"
    And I should not see "Follow" in the ".follow-user-wrapper" element

    # Check when "following" is disabled but still should be possible to unfollow "followed" user.
    And I am logged in as "following"
    And I go to "all-members"
    And I should see the link "Mark Twain"
    And I click "Mark Twain"
    And I should see "Unfollow" in the "#block-socialblue-profile-statistic-block .follow-user-wrapper" element
    And I click "Unfollow"
    And I wait for "3" seconds
    And I should not see "Unfollow" in the "#block-socialblue-profile-statistic-block .follow-user-wrapper" element
    And I should not see "Follow" in the "#block-socialblue-profile-statistic-block .follow-user-wrapper" element

    # Uninstall module and disable back sky theme style.
    And I set the configuration item "socialblue.settings" with key "style" to ""
