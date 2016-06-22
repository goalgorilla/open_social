<?php

/**
 * @file
 * Test cases for the Group module.
 */

namespace Drupal\group\Tests;

use Drupal\group\Entity\Group;

/**
 * Tests the basic functions of the Group module.
 *
 * @package Drupal\group\Tests
 * @ingroup group
 * @group Group
 */
class GroupTest extends GroupTestBase {

  public static $modules = ['group', 'field_ui'];

  /**
   * Basic tests for Group.
   */
  public function testGroup() {
    $web_user = $this->drupalCreateUser([
      'administer group',
      'bypass group access',
    ]);

    // Anonymous should not see the link to the listing.
    $this->assertNoText(t('Group: Contacts Listing'));

    $this->drupalLogin($web_user);

    // Web_user user has the right to view listing.
    $this->assertLink(t('Group: Contacts Listing'));

    $this->clickLink(t('Group: Contacts Listing'));

    // WebUser can add entity content.
    $this->assertLink(t('Add Group'));

    $this->clickLink(t('Add Group'));

    $this->assertFieldByName('title[0][value]', '', 'Title Field, empty');

    $user_ref = $web_user->name->value . ' (' . $web_user->id() . ')';
    $this->assertFieldByName('user_id[0][target_id]', $user_ref, 'User ID reference field points to web_user');

    // Post content, save an instance. Go back to list after saving.
    $edit = ['title[0][value]' => 'Test Group'];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Entity listed.
    $this->assertLink(t('Edit'));
    $this->assertLink(t('Delete'));

    $this->clickLink('Test Group');

    // Entity shown.
    $this->assertText(t('Test Group'));
    $this->assertLink(t('Add Group'));
    $this->assertLink(t('Edit'));
    $this->assertLink(t('Delete'));

    // Delete the entity.
    $this->clickLink('Delete');

    // Confirm deletion.
    $this->assertLink(t('Cancel'));
    $this->drupalPostForm(NULL, [], 'Delete');

    // Back to list, must be empty.
    $this->assertNoText('Test Group');

    // Settings page.
    $this->drupalGet('admin/group/settings');
    $this->assertText(t('Group Settings'));

    // Make sure the field manipulation links are available.
    $this->assertLink(t('Settings'));
    $this->assertLink(t('Manage fields'));
    $this->assertLink(t('Manage form display'));
    $this->assertLink(t('Manage display'));
  }

  /**
   * Test all paths exposed by the module, by permission.
   */
  public function testPaths() {
    // Generate a contact so that we can test the paths against it.
    $group = Group::create(['title' => 'Test Group']);
    $group->save();

    // Gather the test data.
    $test_paths = $this->providerTestPaths($group->id());

    // Run the tests.
    foreach ($test_paths as $test_path) {
      if (!empty($test_path[2])) {
        $user = $this->drupalCreateUser([$test_path[2]]);
        $this->drupalLogin($user);
      }
      else {
        $user = $this->drupalCreateUser();
        $this->drupalLogin($user);
      }
      $this->drupalGet($test_path[1]);
      $this->assertResponse($test_path[0]);
    }
  }

  /**
   * Data provider for testPaths.
   *
   * @param int $gid
   *   The id of an existing Group entity.
   *
   * @return array
   *   Nested array of testing data. Arranged like this:
   *   - Expected response code.
   *   - Path to request.
   *   - Permission for the user.
   */
  protected function providerTestPaths($gid) {
    return [
      [200, '/group/list', 'administer group'],
      [403, '/group/list', ''],
    ];
  }

}
