<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\CountLimitProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class CountLimitProcessorTest extends UnitTestCase {

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
    ];

    $processor_id = 'count_limit';
    $this->processor = new CountLimitProcessor([], $processor_id, []);

    $processor_definitions = [
      $processor_id => [
        'id' => $processor_id,
        'class' => 'Drupal\facets\Plugin\facets\processor\CountLimitProcessor',
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
      'processor_id' => 'count_limit',
      'weights' => [],
      'settings' => ['minimum_items' => 4],
    ]);
    $this->processor->setConfiguration(['minimum_items' => 4]);
    $sorted_results = $this->processor->build($facet, $this->originalResults);

    $this->assertCount(3, $sorted_results);

    $this->assertEquals('llama', $sorted_results[0]->getDisplayValue());
    $this->assertEquals('badger', $sorted_results[1]->getDisplayValue());
    $this->assertEquals('duck', $sorted_results[2]->getDisplayValue());
  }

  /**
   * Tests no filtering happens.
   */
  public function testMinEqualsValue() {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);
    $facet->addProcessor([
      'processor_id' => 'count_limit',
      'weights' => [],
      'settings' => ['minimum_items' => 5],
    ]);
    $this->processor->setConfiguration(['minimum_items' => 5]);

    $sorted_results = $this->processor->build($facet, $this->originalResults);

    $this->assertCount(3, $sorted_results);

    $this->assertEquals('llama', $sorted_results[0]->getDisplayValue());
    $this->assertEquals('badger', $sorted_results[1]->getDisplayValue());
    $this->assertEquals('duck', $sorted_results[2]->getDisplayValue());
  }

  /**
   * Tests between minimum and maximum values.
   */
  public function testBetweenMinAndMaxValue() {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);
    $facet->addProcessor([
      'processor_id' => 'count_limit',
      'weights' => [],
      'settings' => [],
    ]);

    $this->processor->setConfiguration(['minimum_items' => 6, 'maximum_items' => 14]);
    $sorted_results = $this->processor->build($facet, $this->originalResults);
    $this->assertCount(1, $sorted_results);
    $this->assertEquals('llama', $sorted_results[0]->getDisplayValue());

    $this->processor->setConfiguration(['minimum_items' => 60, 'maximum_items' => 140]);
    $sorted_results = $this->processor->build($facet, $this->originalResults);
    $this->assertCount(0, $sorted_results);

    $this->processor->setConfiguration(['minimum_items' => 1, 'maximum_items' => 10]);
    $sorted_results = $this->processor->build($facet, $this->originalResults);
    $this->assertCount(2, $sorted_results);
  }

  /**
   * Tests maximum values.
   */
  public function testMaxValue() {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);
    $facet->addProcessor([
      'processor_id' => 'count_limit',
      'weights' => [],
      'settings' => [],
    ]);

    $this->processor->setConfiguration(['maximum_items' => 14]);
    $sorted_results = $this->processor->build($facet, $this->originalResults);
    $this->assertCount(2, $sorted_results);
    $this->assertEquals('llama', $sorted_results[0]->getDisplayValue());
    $this->assertEquals('badger', $sorted_results[1]->getDisplayValue());

    $this->processor->setConfiguration(['maximum_items' => 140]);
    $sorted_results = $this->processor->build($facet, $this->originalResults);
    $this->assertCount(3, $sorted_results);
    $this->assertEquals('llama', $sorted_results[0]->getDisplayValue());
    $this->assertEquals('badger', $sorted_results[1]->getDisplayValue());
    $this->assertEquals('duck', $sorted_results[2]->getDisplayValue());

    $this->processor->setConfiguration(['maximum_items' => 1]);
    $sorted_results = $this->processor->build($facet, $this->originalResults);
    $this->assertCount(0, $sorted_results);
  }

  /**
   * Tests filtering of results.
   */
  public function testFilterResults() {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);
    $facet->addProcessor([
      'processor_id' => 'count_limit',
      'weights' => [],
      'settings' => ['minimum_items' => 8],
    ]);
    $this->processor->setConfiguration(['minimum_items' => 8]);

    $sorted_results = $this->processor->build($facet, $this->originalResults);

    $this->assertCount(2, $sorted_results);

    $this->assertEquals('llama', $sorted_results[0]->getDisplayValue());
    $this->assertEquals('duck', $sorted_results[2]->getDisplayValue());
  }

  /**
   * Tests configuration.
   */
  public function testConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals(['minimum_items' => 1, 'maximum_items' => 0], $config);
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
