<?php

/**
 * @file
 * Contains \Drupal\admin_toolbar\Tests\AdminToolbarAlterTest.
 */

namespace Drupal\admin_toolbar_tools\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the existence of Admin Toolbar tools new links.
 *
 * @group admin_toolbar_tools
 */
class AdminToolbarToolsAlterTest extends WebTestBase {

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
  public static $modules = array(
    'toolbar',
    'admin_toolbar',
    'admin_toolbar_tools'
  );

  protected function setUp() {
    parent::setUp();

    // Create an administrative user and log it in.
    $this->adminUser = $this->drupalCreateUser(array(
        'access toolbar',
        'access administration pages'
      ));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests for a the hover of sub menus.
   */
  function testAdminToolbarTools() {

    // Test for admin_toolbar_tools if special menu items are added.
    $this->assertRaw('id="toolbar-link-admin_toolbar_tools-flush"');

  }

}
