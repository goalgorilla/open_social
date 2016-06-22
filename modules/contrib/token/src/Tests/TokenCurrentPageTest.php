<?php

/**
 * @file
 * Contains \Drupal\token\Tests\TokenCurrentPageTest.
 */

namespace Drupal\token\Tests;

/**
 * Test the [current-page:*] tokens.
 *
 * @group token
 */
class TokenCurrentPageTest extends TokenTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  function testCurrentPageTokens() {
    $tokens = array(
      '[current-page:title]' => t('Log in'),
      '[current-page:url]' => \Drupal::url('user.login', [], array('absolute' => TRUE)),
      '[current-page:url:absolute]' => \Drupal::url('user.login', [], array('absolute' => TRUE)),
      '[current-page:url:relative]' => \Drupal::url('user.login'),
      '[current-page:url:path]' => '/user/login',
      '[current-page:url:args:value:0]' => 'user',
      '[current-page:url:args:value:1]' => 'login',
      '[current-page:url:args:value:2]' => NULL,
      '[current-page:url:unaliased]' => \Drupal::url('user.login', [], array('absolute' => TRUE, 'alias' => TRUE)),
      '[current-page:page-number]' => 1,
      '[current-page:query:foo]' => NULL,
      '[current-page:query:bar]' => NULL,
      // Deprecated tokens
      '[current-page:arg:0]' => 'user',
      '[current-page:arg:1]' => 'login',
      '[current-page:arg:2]' => NULL,
    );
    $this->assertPageTokens('user/login', $tokens);

    $this->drupalCreateContentType(array('type' => 'page'));
    $node = $this->drupalCreateNode(array('title' => 'Node title', 'path' => array('alias' => '/node-alias')));
    $tokens = array(
      '[current-page:title]' => 'Node title',
      '[current-page:url]' => $node->url('canonical', array('absolute' => TRUE)),
      '[current-page:url:absolute]' => $node->url('canonical', array('absolute' => TRUE)),
      '[current-page:url:relative]' => $node->url(),
      '[current-page:url:alias]' => '/node-alias',
      '[current-page:url:args:value:0]' => 'node-alias',
      '[current-page:url:args:value:1]' => NULL,
      '[current-page:url:unaliased]' => $node->url('canonical', array('absolute' => TRUE, 'alias' => TRUE)),
      '[current-page:url:unaliased:args:value:0]' => 'node',
      '[current-page:url:unaliased:args:value:1]' => $node->id(),
      '[current-page:url:unaliased:args:value:2]' => NULL,
      '[current-page:page-number]' => 1,
      '[current-page:query:foo]' => 'bar',
      '[current-page:query:bar]' => NULL,
      // Deprecated tokens
      '[current-page:arg:0]' => 'node',
      '[current-page:arg:1]' => 1,
      '[current-page:arg:2]' => NULL,
    );
    $this->assertPageTokens("/node/{$node->id()}", $tokens, array(), array('url_options' => array('query' => array('foo' => 'bar'))));
  }
}
