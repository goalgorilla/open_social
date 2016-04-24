<?php

namespace Drupal\Tests\search_api\Unit\Plugin\Processor;

use Drupal\search_api\Plugin\search_api\processor\Stopwords;
use Drupal\search_api\Utility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Stopwords" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\Stopwords
 */
class StopwordsTest extends UnitTestCase {

  use ProcessorTestTrait, TestItemsTrait;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpDataTypePlugin();
    $this->processor = new Stopwords(array(), 'stopwords', array());;
  }

  /**
   * Tests the process() method of the Stopwords processor.
   *
   * @param string $passed_value
   *   The string that should be passed to process().
   * @param string $expected_value
   *   The expected altered string.
   * @param string[] $stopwords
   *   The stopwords with which to configure the test processor.
   *
   * @dataProvider processDataProvider
   */
  public function testProcess($passed_value, $expected_value, array $stopwords) {
    $this->processor->setConfiguration(array('stopwords' => $stopwords));
    $this->invokeMethod('process', array(&$passed_value));
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Data provider for testStopwords().
   *
   * Processor checks for exact case, and tokenized content.
   */
  public function processDataProvider() {
    return array(
      array(
        'or',
        '',
        array('or'),
      ),
      array(
        'orb',
        'orb',
        array('or'),
      ),
      array(
        'for',
        'for',
        array('or'),
      ),
      array(
        'ordor',
        'ordor',
        array('or'),
      ),
      array(
        'ÄÖÜÀÁ<>»«û',
        'ÄÖÜÀÁ<>»«û',
        array('stopword1', 'ÄÖÜÀÁ<>»«', 'stopword3'),
      ),
      array(
        'ÄÖÜÀÁ',
        '',
        array('stopword1', 'ÄÖÜÀÁ', 'stopword3'),
      ),
      array(
        'ÄÖÜÀÁ stopword1',
        'ÄÖÜÀÁ stopword1',
        array('stopword1', 'ÄÖÜÀÁ', 'stopword3'),
      ),
    );
  }

  /**
   * Tests the processor's preprocessSearchQuery() method.
   */
  public function testPreprocessSearchQuery() {
    $index = $this->getMock('Drupal\search_api\IndexInterface');
    $index->expects($this->any())
      ->method('status')
      ->will($this->returnValue(TRUE));
    /** @var \Drupal\search_api\IndexInterface $index */

    $this->processor->setIndex($index);
    $query = Utility::createQuery($index);
    $keys = array('#conjunction' => 'AND', 'foo', 'bar', 'bar foo');
    $query->keys($keys);

    $configuration = array('stopwords' => array('foobar', 'bar', 'barfoo'));
    $this->processor->setConfiguration($configuration);
    $this->processor->preprocessSearchQuery($query);
    unset($keys[1]);
    $this->assertEquals($keys, $query->getKeys());

    $results = Utility::createSearchResultSet($query);
    $this->processor->postprocessSearchResults($results);
    $this->assertEquals(array('bar'), $results->getIgnoredSearchKeys());
  }

}
