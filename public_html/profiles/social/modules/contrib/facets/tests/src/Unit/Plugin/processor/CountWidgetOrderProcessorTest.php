<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\CountWidgetOrderProcessor;
use Drupal\facets\Processor\ProcessorPluginManager;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class CountWidgetOrderProcessorTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\facets\processor\WidgetOrderProcessorInterface
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

    $this->processor = new CountWidgetOrderProcessor([], 'count_widget_order', []);
  }

  /**
   * Tests sorting ascending.
   */
  public function testAscending() {

    $sorted_results = $this->processor->sortResults($this->originalResults, 'ASC');

    $this->assertEquals(5, $sorted_results[0]->getCount());
    $this->assertEquals('badger', $sorted_results[0]->getDisplayValue());
    $this->assertEquals(10, $sorted_results[1]->getCount());
    $this->assertEquals('llama', $sorted_results[1]->getDisplayValue());
    $this->assertEquals(15, $sorted_results[2]->getCount());
    $this->assertEquals('duck', $sorted_results[2]->getDisplayValue());
  }

  /**
   * Tests sorting descending.
   */
  public function testDescending() {

    $sorted_results = $this->processor->sortResults($this->originalResults, 'DESC');

    $this->assertEquals(15, $sorted_results[0]->getCount());
    $this->assertEquals('duck', $sorted_results[0]->getDisplayValue());
    $this->assertEquals(10, $sorted_results[1]->getCount());
    $this->assertEquals('llama', $sorted_results[1]->getDisplayValue());
    $this->assertEquals(5, $sorted_results[2]->getCount());
    $this->assertEquals('badger', $sorted_results[2]->getDisplayValue());
  }

  /**
   * Tests configuration.
   */
  public function testConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals(['sort' => 'ASC'], $config);
  }

  /**
   * Tests build.
   */
  public function testBuild() {
    $processor_definitions = [
      'count_widget_order' => [
        'id' => 'count_widget_order',
        'class' => 'Drupal\facets\Plugin\facets\processor\CountWidgetOrderProcessor',
      ],
    ];
    $manager = $this->getMockBuilder(ProcessorPluginManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $manager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($processor_definitions);
    $manager->expects($this->once())
      ->method('createInstance')
      ->willReturn($this->processor);

    $container_builder = new ContainerBuilder();
    $container_builder->set('plugin.manager.facets.processor', $manager);
    \Drupal::setContainer($container_builder);

    $facet = new Facet(
      [
        'id' => 'the_zoo',
        'results' => $this->originalResults,
        'processor_configs' => $processor_definitions,
      ],
      'facets_facet'
    );
    $built = $this->processor->build($facet, $this->originalResults);

    $this->assertEquals('badger', $built[0]->getDisplayValue());
    $this->assertEquals('llama', $built[1]->getDisplayValue());
    $this->assertEquals('duck', $built[2]->getDisplayValue());
  }

}
