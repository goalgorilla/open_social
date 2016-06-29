<?php

/**
 * @file
 * Contains \Drupal\Tests\token\Kernel\DateTest.
 */

namespace Drupal\Tests\token\Kernel;

/**
 * Tests date tokens.
 *
 * @group token
 */
class DateTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system', 'token_module_test']);
  }

  function testDateTokens() {
    $tokens = array(
      'token_module_test' => '1984',
      'invalid_format' => NULL,
    );

    $this->assertTokens('date', array('date' => 453859200), $tokens);
  }
}
