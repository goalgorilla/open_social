<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\ListItemProcessor;
use Drupal\facets\Result\Result;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\ConfigManager;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class LimitItemProcessorTest extends UnitTestCase {

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
      new Result(1, 1, 10),
      new Result(2, 2, 5),
      new Result(3, 3, 15),
    ];

    $config_manager = $this->getMockBuilder(ConfigManager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $processor_id = 'list_item';
    $this->processor = new ListItemProcessor([], $processor_id, [], $config_manager);
  }

  /**
   * Tests facet build method.
   */
  public function testNoFilter() {
    $field = $this->getMockBuilder(FieldStorageConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
    $field->expects($this->at(0))
      ->method('getSetting')
      ->with('allowed_values_function')
      ->willReturn('');
    $field->expects($this->at(1))
      ->method('getSetting')
      ->with('allowed_values')
      ->willReturn([1 => 'llama', 2 => 'badger', 3 => 'kitten']);

    $config_manager = $this->getMockBuilder(ConfigManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config_manager->expects($this->any())
      ->method('loadConfigEntityByName')
      ->willReturn($field);

    $processor_id = 'list_item';
    $processor = new ListItemProcessor([], $processor_id, [], $config_manager);

    $facet = new Facet([], 'facet');
    $facet->setFieldIdentifier('test_facet');
    $facet->setResults($this->originalResults);
    $facet->addProcessor([
      'processor_id' => 'list_item',
      'weights' => [],
      'settings' => [],
    ]);
    /** @var \Drupal\facets\Result\Result[] $sorted_results */
    $sorted_results = $processor->build($facet, $this->originalResults);

    $this->assertCount(3, $sorted_results);
    $this->assertEquals('llama', $sorted_results[0]->getDisplayValue());
    $this->assertEquals('badger', $sorted_results[1]->getDisplayValue());
    $this->assertEquals('kitten', $sorted_results[2]->getDisplayValue());
  }

  /**
   * Tests configuration.
   */
  public function testConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals([], $config);
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
