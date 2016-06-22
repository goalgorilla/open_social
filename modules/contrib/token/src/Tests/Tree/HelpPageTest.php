<?php

/**
 * @file
 * Contains \Drupal\token\Tests\Tree\HelpPageTest.
 */

namespace Drupal\token\Tests\Tree;

use Drupal\token\Tests\TokenTestBase;

/**
 * Tests token tree on help page.
 *
 * @group token
 */
class HelpPageTest extends TokenTestBase {

  use TokenTreeTestTrait;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['help'];

  public function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests the token browser on the token help page.
   */
  public function testHelpPageTree() {
    $this->drupalGet('admin/help/token');
    $this->assertText('List of the currently available tokens on this site');

    $this->assertTokenGroup('Array');
    $this->assertTokenGroup('Current date');
    $this->assertTokenGroup('Site information');

    $this->assertTokenInTree('[current-date:html_date]', 'current-date');
    $this->assertTokenInTree('[current-date:html_week]', 'current-date');
    $this->assertTokenInTree('[date:html_date]', 'date');
    $this->assertTokenInTree('[date:html_week]', 'date');

    $this->assertTokenInTree('[current-user:account-name]', 'current-user');
    $this->assertTokenInTree('[user:account-name]', 'user');

    $this->assertTokenInTree('[current-page:url:unaliased]', 'current-page--url');
    $this->assertTokenInTree('[current-page:url:unaliased:args]', 'current-page--url--unaliased');
    $this->assertTokenInTree('[user:original:account-name]', 'user--original');

    // Assert some of the restricted tokens to ensure they are shown.
    $this->assertTokenInTree('[user:one-time-login-url]', 'user');
    $this->assertTokenInTree('[user:original:cancel-url]', 'user--original');
  }
}
