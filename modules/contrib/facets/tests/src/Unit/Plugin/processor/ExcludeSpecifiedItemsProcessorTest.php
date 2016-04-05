<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\ExcludeSpecifiedItemsProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class ExcludeSpecifiedItemsProcessorTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\facets\processor\BuildProcessorInterface
   */
  protected $processor;

  /**
   * An array containing the results before the processor has ran.
   *
   * @var \Drupal\facets\Result\Result[]
   */
  protected $originalResults;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->originalResults = [
      new Result('llama', 'llama', 10),
      new Result('badger', 'badger', 5),
      new Result('duck', 'duck', 15),
      new Result('snbke', 'snbke', 10),
      new Result('snake', 'snake', 10),
      new Result('snaake', 'snaake', 10),
      new Result('snaaake', 'snaaake', 10),
      new Result('snaaaake', 'snaaaake', 10),
      new Result('snaaaaake', 'snaaaaake', 10),
      new Result('snaaaaaake', 'snaaaaaake', 10),
    ];

    $processor_id = 'exclude_specified_items';
    $this->processor = new ExcludeSpecifiedItemsProcessor([], $processor_id, []);

    $processor_definitions = [
      $processor_id => [
        'id' => $processor_id,
        'class' => 'Drupal\facets\Plugin\facets\processor\ExcludeSpecifiedItemsProcessor',
      ],
    ];

    $manager = $this->getMockBuilder('Drupal\facets\Processor\ProcessorPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $manager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn($processor_definitions);
    $manager->expects($this->any())
      ->method('createInstance')
      ->willReturn($this->processor);

    $container_builder = new ContainerBuilder();
    $container_builder->set('plugin.manager.facets.processor', $manager);
    \Drupal::setContainer($container_builder);
  }

  /**
   * Tests no filtering happens.
   */
  public function testNoFilter() {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);
    $facet->addProcessor([
      'processor_id' => 'exclude_specified_items',
      'weights' => [],
      'settings' => [
        'exclude' => 'alpaca',
        'regex' => 0,
      ],
    ]);
    $this->processor->setConfiguration([
      'exclude' => 'alpaca',
      'regex' => 0,
    ]);
    $filtered_results = $this->processor->build($facet, $this->originalResults);

    $this->assertCount(count($this->originalResults), $filtered_results);
  }

  /**
   * Tests filtering happens for string filter.
   */
  public function testStringFilter() {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);
    $facet->addProcessor([
      'processor_id' => 'exclude_specified_items',
      'weights' => [],
      'settings' => [
        'exclude' => 'alpaca',
        'regex' => 0,
      ],
    ]);
    $this->processor->setConfiguration([
      'exclude' => 'llama',
      'regex' => 0,
    ]);
    $filtered_results = $this->processor->build($facet, $this->originalResults);

    $this->assertCount((count($this->originalResults) - 1), $filtered_results);

    foreach ($filtered_results as $result) {
      $this->assertNotEquals('llama', $result->getDisplayValue());
    }
  }

  /**
   * Tests filtering happens for regex filter.
   *
   * @dataProvider provideRegexTests
   */
  public function testRegexFilter($regex, $expected_results) {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);
    $facet->addProcessor([
      'processor_id' => 'exclude_specified_items',
      'weights' => [],
      'settings' => [
        'exclude' => 'alpaca',
        'regex' => 0,
      ],
    ]);
    $this->processor->setConfiguration([
      'exclude' => $regex,
      'regex' => 1,
    ]);
    $filtered_results = $this->processor->build($facet, $this->originalResults);

    $this->assertCount(count($expected_results), $filtered_results);

    foreach ($filtered_results as $res) {
      $this->assertTrue(in_array($res->getDisplayValue(), $expected_results));
    }
  }

  /**
   * Provides multiple data sets for ::testRegexFilter.
   */
  public function provideRegexTests() {
    return [
      [
        'test',
        [
          'llama',
          'duck',
          'badger',
          'snake',
          'snaake',
          'snaaake',
          'snaaaake',
          'snaaaaake',
          'snaaaaaake',
          'snbke',
        ],
      ],
      [
        'llama',
        [
          'badger',
          'duck',
          'snake',
          'snaake',
          'snaaake',
          'snaaaake',
          'snaaaaake',
          'snaaaaaake',
          'snbke',
        ],
      ],
      [
        'duck',
        [
          'llama',
          'badger',
          'snake',
          'snaake',
          'snaaake',
          'snaaaake',
          'snaaaaake',
          'snaaaaaake',
          'snbke',
        ],
      ],
      [
        'sn(.*)ke',
        [
          'llama',
          'duck',
          'badger',
        ],
      ],
      [
        'sn(a*)ke',
        [
          'llama',
          'duck',
          'badger',
          'snbke',
        ],
      ],
      [
        'sn(a+)ke',
        [
          'llama',
          'duck',
          'badger',
          'snbke',
        ],
      ],
      [
        'sn(a{3,5})ke',
        [
          'llama',
          'duck',
          'badger',
          'snake',
          'snaake',
          'snaaaaaake',
          'snbke',
        ],
      ],
    ];
  }

  /**
   * Tests configuration.
   */
  public function testConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals(['exclude' => '', 'regex' => 0], $config);
  }

  /**
   * Tests testDescription().
   */
  public function testDescription() {
    $this->assertEquals('', $this->processor->getDescription());
  }

  /**
   * Tests isHidden().
   */
  public function testIsHidden() {
    $this->assertEquals(FALSE, $this->processor->isHidden());
  }

  /**
   * Tests isLocked().
   */
  public function testIsLocked() {
    $this->assertEquals(FALSE, $this->processor->isLocked());
  }

}
