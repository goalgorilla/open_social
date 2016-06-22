<?php

/**
 * @file
 * Contains \Drupal\Tests\token\Kernel\TokenKernelTestBase.
 */

namespace Drupal\Tests\token\Kernel;

use Drupal\KernelTests\KernelTestBase as BaseKernelTestBase;
use Drupal\token\Tests\TokenTestTrait;

/**
 * Helper test class with some added functions for testing.
 */
abstract class KernelTestBase extends BaseKernelTestBase {

  use TokenTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['path', 'token', 'token_module_test', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router', 'url_alias']);
    \Drupal::service('router.builder')->rebuild();
    $this->installConfig(['system']);
  }

}
