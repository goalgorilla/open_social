<?php

namespace Drupal\Tests\facets\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Form\DropdownWidgetForm;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for the select widget form.
 *
 * @group facets
 */
class SelectWidgetFormTest extends UnitTestCase {

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

    $result = new Result('llama', 'Llama', 10);
    $result->setUrl(new Url('llama'));
    $result2 = new Result('badger', 'Badger', 20);
    $result2->setUrl(new Url('badger'));
    $result3 = new Result('duck', 'Duck', 15);
    $result3->setUrl(new Url('duck'));
    $result4 = new Result('alpaca', 'Alpaca', 9);
    $result4->setUrl(new Url('alpaca'));

    $this->originalResults = [
      $result,
      $result2,
      $result3,
      $result4,
    ];

    $url_generator = $this->getMock(UrlGeneratorInterface::class);
    $url_generator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturnCallback(function ($param) {
        return 'http://test/' . $param;
      });
    $string_translation = $this->getMockBuilder(TranslationManager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $container_builder = new ContainerBuilder();
    $container_builder->set('url_generator', $url_generator);
    $container_builder->set('string_translation', $string_translation);
    \Drupal::setContainer($container_builder);
  }

  /**
   * Tests widget form with default settings.
   */
  public function testDefaultSettings() {
    $facet = new Facet(['id' => 'zoo_animal'], 'facet');
    $facet->setResults($this->originalResults);
    $facet->setFieldIdentifier('zoo_animal');

    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$facet]);
    $form = [];

    $widget_form = new DropdownWidgetForm($facet);
    $built_form = $widget_form->buildForm($form, $form_state);

    $this->assertInternalType('array', $built_form);
    $this->assertCount(5, $built_form['zoo_animal']['#options']);
    $this->assertEquals('select', $built_form['zoo_animal']['#type']);

    $expected_links = [
      'http://test/llama' => 'Llama',
      'http://test/badger' => 'Badger',
      'http://test/duck' => 'Duck',
      'http://test/alpaca' => 'Alpaca',
    ];
    foreach ($expected_links as $index => $value) {
      $this->assertEquals($value, $built_form['zoo_animal']['#options'][$index]);
    }
    $this->assertEquals(array('zoo_animal', 'zoo_animal_submit'), array_keys($built_form));
  }

  /**
   * Tests widget form, make sure hiding and showing numbers works.
   */
  public function testHideNumbers() {
    $facet = new Facet([], 'facet');
    $facet->setResults($this->originalResults);
    $facet->setFieldIdentifier('zoo__animal');
    $facet->setWidgetConfigs(['show_numbers' => 0]);

    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$facet]);
    $form = [];

    $widget_form = new DropdownWidgetForm($facet);
    $built_form = $widget_form->buildForm($form, $form_state);

    $this->assertInternalType('array', $built_form);
    $this->assertCount(5, $built_form['zoo__animal']['#options']);
    $expected_links = [
      'http://test/llama' => 'Llama',
      'http://test/badger' => 'Badger',
      'http://test/duck' => 'Duck',
      'http://test/alpaca' => 'Alpaca',
    ];
    foreach ($expected_links as $index => $value) {
      $this->assertEquals($value, $built_form['zoo__animal']['#options'][$index]);
    }

    // Enable the 'show_numbers' setting again to make sure that the switch
    // between those settings works.
    $facet->setWidgetConfigs(['show_numbers' => 1]);

    $built_form = $widget_form->buildForm($form, $form_state);
    $this->assertInternalType('array', $built_form);
    $this->assertCount(5, $built_form['zoo__animal']['#options']);

    $expected_links = [
      'http://test/llama' => 'Llama (10)',
      'http://test/badger' => 'Badger (20)',
      'http://test/duck' => 'Duck (15)',
      'http://test/alpaca' => 'Alpaca (9)',
    ];
    foreach ($expected_links as $index => $value) {
      $this->assertEquals($value, $built_form['zoo__animal']['#options'][$index]);
    }
  }

  /**
   * Tests form default methods.
   */
  public function testForm() {
    $facet = new Facet(['id' => 'donkey'], 'facet');
    $facet->setResults($this->originalResults);
    $facet->setFieldIdentifier('donkey');

    $form = new DropdownWidgetForm($facet);

    $this->assertEquals('facets_dropdown_widget', $form->getBaseFormId());
    $this->assertEquals('facets_dropdown_widget__donkey', $form->getFormId());
  }

}
