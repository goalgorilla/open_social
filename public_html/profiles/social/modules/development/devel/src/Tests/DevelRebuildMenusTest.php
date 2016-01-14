<?php

/**
 * @file
 * Contains \Drupal\devel\Tests\DevelRebuildMenusTest.
 */

namespace Drupal\devel\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests routes rebuild.
 *
 * @group devel
 */
class DevelRebuildMenusTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('devel');

  /**
   * Set up test.
   */
  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($web_user);
  }

  /**
   * Test routes rebuild.
   */
  public function testDevelRebuildMenus() {
    $this->drupalGet('devel/menu/reset');
    $this->assertResponse(200);
    $this->drupalPostForm('devel/menu/reset', array(), t('Rebuild'));
    $this->assertText(t('The menu router has been rebuilt.'));
  }

}
