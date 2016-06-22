<?php

/**
 * @file
 * Contains \Drupal\Tests\token\Kernel\ArrayTest.
 */
namespace Drupal\Tests\token\Kernel;

/**
 * Tests array tokens.
 *
 * @group token
 */
class ArrayTest extends KernelTestBase {

  function testArrayTokens() {
    // Test a simple array.
    $array = array(0 => 'a', 1 => 'b', 2 => 'c', 4 => 'd');
    $tokens = array(
      'first' => 'a',
      'last' => 'd',
      'value:0' => 'a',
      'value:2' => 'c',
      'count' => 4,
      'keys' => '0, 1, 2, 4',
      'keys:value:3' => '4',
      'keys:join' => '0124',
      'reversed' => 'd, c, b, a',
      'reversed:keys' => '4, 2, 1, 0',
      'join:/' => 'a/b/c/d',
      'join' => 'abcd',
      'join:, ' => 'a, b, c, d',
      'join: ' => 'a b c d',
    );
    $this->assertTokens('array', array('array' => $array), $tokens);

    // Test a mixed simple and render array.
    // 2 => c, 0 => a, 4 => d, 1 => b
    $array = array(
      '#property' => 'value',
      0 => 'a',
      1 => array('#markup' => 'b', '#weight' => 0.01),
      2 => array('#markup' => 'c', '#weight' => -10),
      4 => array('#markup' => 'd', '#weight' => 0),
    );
    $tokens = array(
      'first' => 'c',
      'last' => 'b',
      'value:0' => 'a',
      'value:2' => 'c',
      'count' => 4,
      'keys' => '2, 0, 4, 1',
      'keys:value:3' => '1',
      'keys:join' => '2041',
      'reversed' => 'b, d, a, c',
      'reversed:keys' => '1, 4, 0, 2',
      'join:/' => 'c/a/d/b',
      'join' => 'cadb',
      'join:, ' => 'c, a, d, b',
      'join: ' => 'c a d b',
    );
    $this->assertTokens('array', array('array' => $array), $tokens);
  }
}
