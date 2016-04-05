<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\Core\Language\Language;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\TranslateTaxonomyProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class TranslateTaxonomyProcessorTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\facets\processor\WidgetOrderProcessorInterface
   */
  protected $processor;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->processor = new TranslateTaxonomyProcessor([], 'translate_taxonomy', []);

    $this->createMocks();
  }

  /**
   * Tests that results were correctly changed.
   */
  public function testResultsChanged() {
    /** @var \Drupal\facets\Result\ResultInterface[] $original_results */
    $original_results = [
      new Result(1, 1, 5),
    ];

    $facet = new Facet([], 'facet');
    $facet->setResults($original_results);

    $expected_results = [
      ['tid' => 1, 'name' => 'Burrowing owl'],
    ];

    foreach ($expected_results as $key => $expected) {
      $this->assertEquals($expected['tid'], $original_results[$key]->getRawValue());
      $this->assertEquals($expected['tid'], $original_results[$key]->getDisplayValue());
    }

    $filtered_results = $this->processor->build($facet, $original_results);

    foreach ($expected_results as $key => $expected) {
      $this->assertEquals($expected['tid'], $filtered_results[$key]->getRawValue());
      $this->assertEquals($expected['name'], $filtered_results[$key]->getDisplayValue());
    }
  }

  /**
   * Creates and sets up the container to be used in tests.
   */
  protected function createMocks() {
    $term = $this->getMockBuilder('\Drupal\taxonomy\Entity\Term')
      ->disableOriginalConstructor()
      ->getMock();
    $term->expects($this->any())
      ->method('getName')
      ->willReturn('Burrowing owl');

    $term_storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $term_storage->expects($this->any())
      ->method('load')
      ->willReturn($term);
    $entity_manager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getStorage')
      ->willReturn($term_storage);

    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $language = new Language(['langcode' => 'en']);
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language));

    $container = new ContainerBuilder();
    $container->set('entity.manager', $entity_manager);
    $container->set('language_manager', $language_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests configuration.
   */
  public function testConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals([], $config);
  }

}
