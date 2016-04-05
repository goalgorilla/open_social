<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\HideActiveItemsProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class HideActiveItemsProcessorTest extends UnitTestCase {

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
      new Result('badger', 'badger', 15),
      new Result('duck', 'duck', 15),
    ];

    $this->processor = new HideActiveItemsProcessor([], 'hide_non_narrowing_result_processor', []);
  }

  /**
   * Tests filtering of results.
   */
  public function testNoFilterResults() {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);

    $filtered_results = $this->processor->build($facet, $this->originalResults);

    $this->assertCount(3, $filtered_results);

    $this->assertEquals(10, $filtered_results[0]->getCount());
    $this->assertEquals('llama', $filtered_results[0]->getDisplayValue());
    $this->assertEquals(15, $filtered_results[1]->getCount());
    $this->assertEquals('badger', $filtered_results[1]->getDisplayValue());
    $this->assertEquals(15, $filtered_results[2]->getCount());
    $this->assertEquals('duck', $filtered_results[2]->getDisplayValue());
  }

  /**
   * Tests filtering of results.
   */
  public function testFilterResults() {
    $results = $this->originalResults;
    $results[2]->setActiveState(TRUE);

    $facet = new Facet([], 'facet');
    $facet->setResults($results);

    $filtered_results = $this->processor->build($facet, $results);

    $this->assertCount(2, $filtered_results);

    $this->assertEquals(10, $filtered_results[0]->getCount());
    $this->assertEquals('llama', $filtered_results[0]->getDisplayValue());
    $this->assertEquals(15, $filtered_results[1]->getCount());
    $this->assertEquals('badger', $filtered_results[1]->getDisplayValue());
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
