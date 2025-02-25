@api
Feature: Create Post with Photo and add it to an existing Album
  Benefit: In order to share knowledge with people
  Role: As a Verified
  Goal/desire: I want to create Posts with photo and add it to the existing album.

  Scenario: Successfully add an image to the album via the stream post form

    Given I enable the module "social_album"
    And users:
      | name     | status | pass     | roles    |
      | PostUser |      1 | PostUser | verified |
    And I am logged in as "PostUser"

    When I create an album using its creation page:
      | Title        | This is my first album. |

    Then I should see the album I just created

    And I am on "/stream"

    And I should see "Add images"
    And I should see "This is my first album." in the ".field--name-field-album" element
