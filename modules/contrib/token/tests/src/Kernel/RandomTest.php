<?php

/**
 * @file
 * Contains \Drupal\Tests\token\Kernel\RandomTest.
 */
namespace Drupal\Tests\token\Kernel;

/**
 * Tests random tokens.
 *
 * @group token
 */
class RandomTest extends KernelTestBase {

  function testRandomTokens() {
    $tokens = array(
      'number' => '[0-9]{1,}',
      'hash:md5' => '[0-9a-f]{32}',
      'hash:sha1' => '[0-9a-f]{40}',
      'hash:sha256' => '[0-9a-f]{64}',
      'hash:invalid-algo' => NULL,
    );

    $first_set = $this->assertTokens('random', array(), $tokens, array('regex' => TRUE));
    $second_set = $this->assertTokens('random', array(), $tokens, array('regex' => TRUE));
    foreach ($first_set as $token => $value) {
      $this->assertNotIdentical($first_set[$token], $second_set[$token]);
    }
  }
}
