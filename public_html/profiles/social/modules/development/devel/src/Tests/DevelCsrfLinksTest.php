<?php

/**
 * @file
 * Contains \Drupal\devel\Tests\DevelCsrfLinksTest.
 */

namespace Drupal\devel\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests CSFR protected links.
 *
 * @group devel
 */
class DevelCsrfLinksTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('devel', 'block');

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $develUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // CSFR protected links currently appears only in the devel menu.
    // Place the devel menu block so we can ensure that these link works
    // properly.
    $this->drupalPlaceBlock('system_menu_block:devel');

    $this->develUser = $this->drupalCreateUser(['access devel information', 'administer site configuration']);
  }

  /**
   * Tests CSFR protected links.
   */
  public function testCsrfProtection() {
    $this->drupalLogin($this->develUser);

    // Ensure CSRF link are not accessible directly.
    $this->drupalGet('devel/run-cron');
    $this->assertResponse(403);
    $this->drupalGet('devel/cache/clear');
    $this->assertResponse(403);

    // Ensure clear cache link works propery.
    $this->assertLink('Cache clear');
    $this->clickLink('Cache clear');
    $this->assertText('Cache cleared.');

    // Ensure run cron link works propery.
    $this->assertLink('Run cron');
    $this->clickLink('Run cron');
    $this->assertText('Cron ran successfully.');

    // Ensure CSRF protected links work properly after change session.
    $this->drupalLogout();
    $this->drupalLogin($this->develUser);

    $this->assertLink('Cache clear');
    $this->clickLink('Cache clear');
    $this->assertText('Cache cleared.');

    $this->assertLink('Run cron');
    $this->clickLink('Run cron');
    $this->assertText('Cron ran successfully.');
  }

}
