<?php

/**
 * @file
 * Contains \Drupal\token\Tests\Tree\TreeTest.
 */

namespace Drupal\token\Tests\Tree;

use Drupal\Component\Serialization\Json;
use Drupal\token\Tests\TokenTestBase;

/**
 * Tests token tree page.
 *
 * @group token
 */
class TreeTest extends TokenTestBase {

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
  public static $modules = ['node'];

  public function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser(['administer account settings']);
    $this->drupalLogin($this->account);
  }

  /**
   * Test various tokens that are possible on the site.
   */
  public function testAllTokens() {
    $this->drupalGet($this->getTokenTreeUrl(['token_types' => 'all']));

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
  }

  /**
   * Test various tokens that are possible on the site.
   */
  public function testGlobalTokens() {
    $this->drupalGet($this->getTokenTreeUrl());

    $this->assertTokenGroup('Current date');
    $this->assertTokenGroup('Site information');

    // Assert that non-global tokens are not listed.
    $this->assertTokenNotInTree('[user:account-name]', 'user');
    $this->assertTokenNotInTree('[user:original:account-name]', 'user--original');

    // Assert some of the global tokens, just to be sure.
    $this->assertTokenInTree('[current-date:html_date]', 'current-date');
    $this->assertTokenInTree('[current-date:html_week]', 'current-date');

    $this->assertTokenInTree('[current-user:account-name]', 'current-user');

    $this->assertTokenInTree('[current-page:url:unaliased]', 'current-page--url');
    $this->assertTokenInTree('[current-page:url:unaliased:args]', 'current-page--url--unaliased');
  }

  /**
   * Tests if the token browser displays the user tokens.
   */
  public function testUserTokens() {
    $this->drupalGet($this->getTokenTreeUrl(['token_types' => ['user']]));

    $this->assertTokenGroup('Users');

    $this->assertTokenInTree('[user:account-name]', 'user');
    $this->assertTokenInTree('[user:original:account-name]', 'user--original');

    // Assert some of the restricted tokens to ensure they are not shown.
    $this->assertTokenNotInTree('[user:one-time-login-url]', 'user');
    $this->assertTokenNotInTree('[user:original:cancel-url]', 'user--original');

    // Request with show_restricted set to TRUE to show restricted tokens and
    // check for them.
    $this->drupalGet($this->getTokenTreeUrl(['token_types' => ['user'], 'show_restricted' => TRUE]));
    $this->assertEqual('MISS', $this->drupalGetHeader('x-drupal-dynamic-cache'), 'Cache was not hit');
    $this->assertTokenInTree('[user:one-time-login-url]', 'user');
    $this->assertTokenInTree('[user:original:cancel-url]', 'user--original');
  }

  /**
   * Tests if the token browser displays the node tokens.
   */
  public function testNodeTokens() {
    $this->drupalGet($this->getTokenTreeUrl(['token_types' => ['node']]));

    $this->assertTokenGroup('Nodes');

    $this->assertTokenInTree('[node:body]', 'node');
    $this->assertTokenInTree('[node:author:original:account-name]', 'node--author--original');
  }

  /**
   * Get the URL for the token tree based on the specified options.
   *
   * The token tree route's URL requires CSRF and cannot be generated in the
   * test code. The CSRF token generated using the test runner's session is
   * different from the session inside the test environment. This is why the
   * link has to be generated inside the environment.
   *
   * This function calls a page in token_module_test module which generates the
   * link and the token. This then replaces the options query parameter with the
   * specified options.
   *
   * @param array $options
   *   The options for the token tree browser.
   *
   * @return string
   *   The complete URL of the token tree browser with the CSRF token.
   */
  protected function getTokenTreeUrl($options = []) {
    $this->drupalGet('token_module_test/browse');
    $links = $this->xpath('//a[contains(@href, :href)]/@href', array(':href' => 'token/tree'));
    $link = $this->getAbsoluteUrl((string) current($links));
    if (!empty($options)) {
      $options = Json::encode($options);
      $link = str_replace('options=%5B%5D', 'options=' . urlencode($options), $link);
    }
    return $link;
  }
}
