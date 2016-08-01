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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['toolbar', 'breakpoint', 'admin_toolbar'];

  /**
   * A test user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'access toolbar',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests for a the hover of sub menus.
   */
  function testAdminToolbar() {
    // Assert that expanded links are present in the HTML.
    $this->assertRaw('class="toolbar-icon toolbar-icon-user-admin-index"');
  }
}
