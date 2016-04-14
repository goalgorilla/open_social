<?php

namespace Drupal\Tests\search_api\Unit\Plugin\Processor;

use Drupal\search_api\Plugin\search_api\processor\HtmlFilter;
use Drupal\search_api\Utility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "HTML filter" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\HtmlFilter
 */
class HtmlFilterTest extends UnitTestCase {

  use ProcessorTestTrait;

  /**
   * Creates a new processor object for use in the tests.
   */
  public function setUp() {
    parent::setUp();
    $this->processor = new HtmlFilter(array(), 'html_filter', array());
  }

  /**
   * Tests preprocessing field values with "title" settings.
   *
   * @param string $passed_value
   *   The value that should be passed into process().
   * @param string $expected_value
   *   The expected processed value.
   * @param bool $title_config
   *   The value to set for the processor's "title" setting.
   *
   * @dataProvider titleConfigurationDataProvider
   */
  public function testTitleConfiguration($passed_value, $expected_value, $title_config) {
    $configuration = array(
      'tags' => array(),
      'title' => $title_config,
      'alt' => FALSE,
    );
    $this->processor->setConfiguration($configuration);
    $type = 'text';
    $this->invokeMethod('processFieldValue', array(&$passed_value, &$type));
    $this->assertEquals($expected_value, $passed_value);

  }

  /**
   * Data provider for testTitleConfiguration().
   *
   * @return array
   *   An array of argument arrays for testTitleConfiguration().
   */
  public function titleConfigurationDataProvider() {
    return array(
      array('word', 'word', FALSE),
      array('word', 'word', TRUE),
      array('<div>word</div>', 'word', TRUE),
      array('<div title="TITLE">word</div>', 'TITLE word', TRUE),
      array('<div title="TITLE">word</div>', 'word', FALSE),
      array('<div data-title="TITLE">word</div>', 'word', TRUE),
      array('<div title="TITLE">word</a>', 'TITLE word', TRUE),
    );
  }

  /**
   * Tests preprocessing field values with "alt" settings.
   *
   * @param string $passed_value
   *   The value that should be passed into process().
   * @param mixed $expected_value
   *   The expected processed value.
   * @param bool $alt_config
   *   The value to set for the processor's "alt" setting.
   *
   * @dataProvider altConfigurationDataProvider
   */
  public function testAltConfiguration($passed_value, $expected_value, $alt_config) {
    $configuration = array(
      'tags' => array('img' => '2'),
      'title' => FALSE,
      'alt' => $alt_config,
    );
    $this->processor->setConfiguration($configuration);
    $type = 'text';
    $this->invokeMethod('processFieldValue', array(&$passed_value, &$type));
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Data provider method for testAltConfiguration().
   *
   * @return array
   *   An array of argument arrays for testAltConfiguration().
   */
  public function altConfigurationDataProvider() {
    return array(
      array('word', array(Utility::createTextToken('word')), FALSE),
      array('word', array(Utility::createTextToken('word')), TRUE),
      array(
        '<img src="href" />word',
        array(Utility::createTextToken('word')),
        TRUE,
      ),
      array(
        '<img alt="ALT"/> word',
        array(
          Utility::createTextToken('ALT', 2),
          Utility::createTextToken('word'),
        ),
        TRUE,
      ),
      array(
        '<img alt="ALT" /> word',
        array(Utility::createTextToken('word')),
        FALSE,
      ),
      array(
        '<img data-alt="ALT"/> word',
        array(Utility::createTextToken('word')),
        TRUE,
      ),
      array(
        '<img src="href" alt="ALT" title="Bar" /> word </a>',
        array(
          Utility::createTextToken('ALT', 2),
          Utility::createTextToken('word'),
        ),
        TRUE,
      ),
    );
  }

  /**
   * Tests preprocessing field values with "alt" settings.
   *
   * @param string $passed_value
   *   The value that should be passed into process().
   * @param mixed $expected_value
   *   The expected processed value.
   * @param float[] $tags_config
   *   The value to set for the processor's "tags" setting.
   *
   * @dataProvider tagConfigurationDataProvider
   */
  public function testTagConfiguration($passed_value, $expected_value, array $tags_config) {
    $configuration = array(
      'tags' => $tags_config,
      'title' => TRUE,
      'alt' => TRUE,
    );
    $this->processor->setConfiguration($configuration);
    $type = 'text';
    $this->invokeMethod('processFieldValue', array(&$passed_value, &$type));
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Data provider method for testTagConfiguration().
   *
   * @return array
   *   An array of argument arrays for testTagConfiguration().
   */
  public function tagConfigurationDataProvider() {
    $complex_test = array(
      '<h2>Foo Bar <em>Baz</em></h2>

<p>Bla Bla Bla. <strong title="Foobar">Important:</strong> Bla.</p>
<img src="/foo.png" alt="Some picture" />
<span>This is hidden</span>',
      array(
        Utility::createTextToken('Foo Bar', 3.0),
        Utility::createTextToken('Baz', 4.5),
        Utility::createTextToken('Bla Bla Bla.', 1.0),
        Utility::createTextToken('Foobar Important:', 2.0),
        Utility::createTextToken('Bla.', 1.0),
        Utility::createTextToken('Some picture', 0.5),
      ),
      array(
        'em' => 1.5,
        'strong' => 2.0,
        'h2' => 3.0,
        'img' => 0.5,
        'span' => 0,
      ),
    );
    $tags_config = array('h2' => '2');
    return array(
      array('h2word', 'h2word', array()),
      array('h2word', array(Utility::createTextToken('h2word')), $tags_config),
      array(
        'foo bar <h2> h2word </h2>',
        array(
          Utility::createTextToken('foo bar'),
          Utility::createTextToken('h2word', 2.0),
        ),
        $tags_config,
      ),
      array(
        'foo bar <h2>h2word</h2>',
        array(
          Utility::createTextToken('foo bar'),
          Utility::createTextToken('h2word', 2.0),
        ),
        $tags_config,
      ),
      array(
        '<div>word</div>',
        array(Utility::createTextToken('word', 2)),
        array('div' => 2),
      ),
      $complex_test,
    );
  }

  /**
   * Tests whether strings are correctly handled.
   *
   * String field handling should be completely independent of configuration.
   *
   * @param array $config
   *   The configuration to set on the processor.
   *
   * @dataProvider stringProcessingDataProvider
   */
  public function testStringProcessing(array $config) {
    $this->processor->setConfiguration($config);

    $passed_value = '<h2>Foo Bar <em>Baz</em></h2>

<p>Bla Bla Bla. <strong title="Foobar">Important:</strong> Bla.</p>
<img src="/foo.png" alt="Some picture" />
<span>This is hidden</span>';
    $expected_value = preg_replace('/\s+/', ' ', strip_tags($passed_value));

    $type = 'string';
    $this->invokeMethod('processFieldValue', array(&$passed_value, &$type));
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Provides a few sets of HTML filter configuration.
   *
   * @return array
   *   An array of argument arrays for testStringProcessing(), where each array
   *   contains a HTML filter configuration as the only value.
   */
  public function stringProcessingDataProvider() {
    $configs = array();
    $configs[] = array(array());
    $config['tags'] = array(
      'h2' => 2.0,
      'span' => 4.0,
      'strong' => 1.5,
      'p' => 0,
    );
    $configs[] = array($config);
    $config['title'] = TRUE;
    $configs[] = array($config);
    $config['alt'] = TRUE;
    $configs[] = array($config);
    unset($config['tags']);
    $configs[] = array($config);
    return $configs;
  }

}
