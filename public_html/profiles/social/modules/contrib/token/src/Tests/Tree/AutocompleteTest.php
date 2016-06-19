<?php

/**
 * @file
 * Contains \Drupal\token\Tests\Tree\AutocompleteTest.
 */

namespace Drupal\token\Tests\Tree;

use Drupal\token\Tests\TokenTestBase;

/**
 * Test token autocomplete.
 *
 * @group token
 */
class AutocompleteTest extends TokenTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * Tests autocomplete for node tokens.
   */
  public function testNodeAutocomplete() {
    $url_prefix = "token/autocomplete/node/";

    $url = $url_prefix . 'Title of [nod';
    $response = $this->drupalGetJSON($url);

    $this->assertTrue(isset($response['[node:nid]']));
    $this->assertTrue(isset($response['[node:author]']));
    $this->assertTrue(isset($response['[node:url]']));
    $this->assertTrue(isset($response['[node:url:']));

    $url = $url_prefix . 'Title of [node:url:';
    $response = $this->drupalGetJSON($url);

    $this->assertTrue(isset($response['[node:url:path]']));
    $this->assertTrue(isset($response['[node:url:absolute]']));
  }

  /**
   * Tests autocomplete for user tokens.
   */
  public function testUserAutocomplete() {
    $url_prefix = "token/autocomplete/user/";

    $url = $url_prefix . 'Name of the [us';
    $response = $this->drupalGetJSON($url);

    $this->assertTrue(isset($response['[user:uid]']));
    $this->assertTrue(isset($response['[user:original]']));
    $this->assertTrue(isset($response['[user:url]']));
    $this->assertTrue(isset($response['[user:url:']));

    $url = $url_prefix . 'Title of [user:original:';
    $response = $this->drupalGetJSON($url);

    $this->assertTrue(isset($response['[user:original:uid]']));
  }
}
