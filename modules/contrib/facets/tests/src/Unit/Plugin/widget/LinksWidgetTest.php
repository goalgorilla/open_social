<?php

namespace Drupal\Tests\facets\Unit\Plugin\widget;

use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\widget\LinksWidget;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for widget.
 *
 * @group facets
 */
class LinksWidgetTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \drupal\facets\Widget\WidgetInterface
   */
  protected $widget;

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

    /** @var \Drupal\facets\Result\Result[] $original_results */
    $original_results = [
      new Result('llama', 'Llama', 10),
      new Result('badger', 'Badger', 20),
      new Result('duck', 'Duck', 15),
      new Result('alpaca', 'Alpaca', 9),
    ];

    foreach ($original_results as $original_result) {
      $original_result->setUrl(new Url('test'));
    }
    $this->originalResults = $original_results;

    $this->widget = new LinksWidget();
  }

  /**
   * Tests widget without filters.
   */
  public function testNoFilterResults() {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);
    $facet->setWidgetConfigs(['show_numbers' => 1]);

    $output = $this->widget->build($facet);

    $this->assertInternalType('array', $output);
    $this->assertCount(4, $output['#items']);

    $expected_links = ['Llama (10)', 'Badger (20)', 'Duck (15)', 'Alpaca (9)'];
    foreach ($expected_links as $index => $value) {
      $this->assertInstanceOf('\Drupal\Core\Link', $output['#items'][$index]);
      $this->assertEquals($value, $output['#items'][$index]->getText());
    }
  }

  /**
   * Test widget with 2 active items.
   */
  public function testActiveItems() {
    $original_results = $this->originalResults;
    $original_results[0]->setActiveState(TRUE);
    $original_results[3]->setActiveState(TRUE);

    $facet = new Facet([], 'facet');
    $facet->setResults($original_results);
    $facet->setWidgetConfigs(['show_numbers' => 1]);

    $output = $this->widget->build($facet);

    $this->assertInternalType('array', $output);
    $this->assertCount(4, $output['#items']);

    $expected_links = [
      '(-) Llama (10)',
      'Badger (20)',
      'Duck (15)',
      '(-) Alpaca (9)',
    ];
    foreach ($expected_links as $index => $value) {
      $this->assertInstanceOf('\Drupal\Core\Link', $output['#items'][$index]);
      $this->assertEquals($value, $output['#items'][$index]->getText());
    }
  }

  /**
   * Tests widget, make sure hiding and showing numbers works.
   */
  public function testHideNumbers() {
    $original_results = $this->originalResults;
    $original_results[1]->setActiveState(TRUE);

    $facet = new Facet([], 'facet');
    $facet->setResults($original_results);
    $facet->setWidgetConfigs(['show_numbers' => 0]);

    $output = $this->widget->build($facet);

    $this->assertInternalType('array', $output);
    $this->assertCount(4, $output['#items']);

    $expected_links = ['Llama', '(-) Badger', 'Duck', 'Alpaca'];
    foreach ($expected_links as $index => $value) {
      $this->assertInstanceOf('\Drupal\Core\Link', $output['#items'][$index]);
      $this->assertEquals($value, $output['#items'][$index]->getText());
    }

    // Enable the 'show_numbers' setting again to make sure that the switch
    // between those settings works.
    $facet->setWidgetConfigs(['show_numbers' => 1]);

    $output = $this->widget->build($facet);

    $this->assertInternalType('array', $output);
    $this->assertCount(4, $output['#items']);

    $expected_links = [
      'Llama (10)',
      '(-) Badger (20)',
      'Duck (15)',
      'Alpaca (9)',
    ];
    foreach ($expected_links as $index => $value) {
      $this->assertInstanceOf('\Drupal\Core\Link', $output['#items'][$index]);
      $this->assertEquals($value, $output['#items'][$index]->getText());
    }
  }

}
