<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\DisplayValueWidgetOrderProcessor;
use Drupal\facets\Processor\ProcessorPluginManager;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class DisplayValueWidgetOrderProcessorTest extends UnitTestCase {

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
      new Result('thetans', 'thetans', 10),
      new Result('xenu', 'xenu', 5),
      new Result('Tom', 'Tom', 15),
      new Result('Hubbard', 'Hubbard', 666),
      new Result('FALSE', 'FALSE', 1),
      new Result('1977', '1977', 20),
      new Result('2', '2', 22),
    ];

    $this->processor = new DisplayValueWidgetOrderProcessor([], 'display_value_widget_order', []);
  }

  /**
   * Tests sorting ascending.
   */
  public function testAscending() {
    $sorted_results = $this->processor->sortResults($this->originalResults, 'ASC');
    $expected_values = [
      '2',
      '1977',
      'FALSE',
      'Hubbard',
      'thetans',
      'Tom',
      'xenu',
    ];
    foreach ($expected_values as $index => $value) {
      $this->assertEquals($value, $sorted_results[$index]->getDisplayValue());
    }
  }

  /**
   * Tests sorting descending.
   */
  public function testDescending() {
    $sorted_results = $this->processor->sortResults($this->originalResults, 'DESC');
    $expected_values = array_reverse([
      '2',
      '1977',
      'FALSE',
      'Hubbard',
      'thetans',
      'Tom',
      'xenu',
    ]);
    foreach ($expected_values as $index => $value) {
      $this->assertEquals($value, $sorted_results[$index]->getDisplayValue());
    }
  }

  /**
   * Tests that sorting uses the display value.
   */
  public function testUseActualDisplayValue() {
    $original = [
      new Result('bb_test', 'Test AA', 10),
      new Result('aa_test', 'Test BB', 10),
    ];

    $sorted_results = $this->processor->sortResults($original, 'DESC');

    $this->assertEquals('Test BB', $sorted_results[0]->getDisplayValue());
    $this->assertEquals('Test AA', $sorted_results[1]->getDisplayValue());
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
      'display_value_widget_order' => [
        'id' => 'display_value_widget_order',
        'class' => 'Drupal\facets\Plugin\facets\processor\DisplayValueWidgetOrderProcessor',
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

    $this->assertEquals('2', $built[0]->getDisplayValue());
    $this->assertEquals('1977', $built[1]->getDisplayValue());
    $this->assertEquals('FALSE', $built[2]->getDisplayValue());
    $this->assertEquals('Hubbard', $built[3]->getDisplayValue());
    $this->assertEquals('thetans', $built[4]->getDisplayValue());
    $this->assertEquals('Tom', $built[5]->getDisplayValue());
    $this->assertEquals('xenu', $built[6]->getDisplayValue());
  }

}
