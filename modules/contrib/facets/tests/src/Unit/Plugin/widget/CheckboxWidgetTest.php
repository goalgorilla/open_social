<?php

namespace Drupal\Tests\facets\Unit\Plugin\widget;

use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\widget\CheckboxWidget;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for widget.
 *
 * @group facets
 */
class CheckboxWidgetTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\facets\Plugin\facets\widget\CheckboxWidget
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

    $form_builder = $this->getMockBuilder('\Drupal\Core\Form\FormBuilder')
      ->disableOriginalConstructor()
      ->getMock();
    $form_builder->expects($this->once())
      ->method('getForm')
      ->willReturn('build');

    $string_translation = $this->getMockBuilder('\Drupal\Core\StringTranslation\TranslationManager')
      ->disableOriginalConstructor()
      ->getMock();

    $container_builder = new ContainerBuilder();
    $container_builder->set('form_builder', $form_builder);
    $container_builder->set('string_translation', $string_translation);
    \Drupal::setContainer($container_builder);

    $this->widget = new CheckboxWidget();
  }

  /**
   * Tests widget with default settings.
   */
  public function testDefaultSettings() {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);
    $facet->setFieldIdentifier('test_field');

    $built_form = $this->widget->build($facet);
    $this->assertEquals('build', $built_form);
  }

}
