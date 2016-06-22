<?php

/**
 * @file
 * Contains \Drupal\group\Tests\GroupTestBase.
 */

namespace Drupal\group\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * A standardized base class for Group tests.
 *
 * Use this base class if the Group module being tested requires menus, local
 * tasks, and actions.
 */
abstract class GroupTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Install Drupal.
    parent::setUp();
    // We can call extra protected methods here.
  }

}
