<?php

namespace Drupal\Tests\search_api\Unit\Plugin\Processor;

use Drupal\search_api\Plugin\search_api\processor\IgnoreCase;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Ignore case" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\IgnoreCase
 */
class IgnoreCaseTest extends UnitTestCase {

  use ProcessorTestTrait;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();
    $this->processor = new IgnoreCase(array(), 'string', array());
  }

  /**
   * Tests the process() method.
   *
   * @param string $passed_value
   *   The value that should be passed into process().
   * @param string $expected_value
   *   The expected processed value.
   *
   * @dataProvider processDataProvider
   */
  public function testProcess($passed_value, $expected_value) {
    $this->invokeMethod('process', array(&$passed_value));
    $this->assertEquals($passed_value, $expected_value);
  }

  /**
   * Provides sets of arguments for testProcess().
   *
   * @return array[]
   *   Arrays of arguments for testProcess().
   */
  public function processDataProvider() {
    return array(
      array('Foo bar', 'foo bar'),
      array('foo Bar', 'foo bar'),
      array('Foo Bar', 'foo bar'),
      array('Foo bar BaZ, ÄÖÜÀÁ<>»«.', 'foo bar baz, äöüàá<>»«.'),
    );
  }

}
