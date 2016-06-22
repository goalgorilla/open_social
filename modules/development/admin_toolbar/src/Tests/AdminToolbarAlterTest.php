<?php

/**
 * @file
 * Contains \Drupal\admin_toolbar\Tests\AdminToolbarAlterTest.
 */

namespace Drupal\admin_toolbar\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the existence of Admin Toolbar module.
 *
 * @group admin_toolbar
 */
class AdminToolbarAlterTest extends WebTestBase {

  /**
   * A user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('toolbar', 'admin_toolbar');

  protected function setUp() {
    parent::setUp();

    // Create an administrative user and log it in.
    $this->adminUser = $this->drupalCreateUser(array('access toolbar', 'access administration pages'));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests for a the hover of sub menus.
   */
  function testAdminToolbar() {

    // Assert that expanded links are present in HTML.
    // Test with the site configuration link that must be there whatever modules exists.
    $this->assertRaw('id="toolbar-link-system-admin_config_system"');

  }
}
