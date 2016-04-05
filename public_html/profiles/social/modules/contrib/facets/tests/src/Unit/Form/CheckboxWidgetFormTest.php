<?php

namespace Drupal\Tests\facets\Unit\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Form\CheckboxWidgetForm;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for the checkbox widget form.
 *
 * @group facets
 */
class CheckboxWidgetFormTest extends UnitTestCase {

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
    $original_results[1]->setActiveState(TRUE);

    $this->originalResults = $original_results;
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

    $widget_form = new CheckboxWidgetForm($facet);
    $built_form = $widget_form->buildForm($form, $form_state);

    $this->assertInternalType('array', $built_form);
    $this->assertCount(4, $built_form['zoo_animal']['#options']);
    $this->assertEquals('checkboxes', $built_form['zoo_animal']['#type']);

    $expected_links = [
      'llama' => 'Llama',
      'badger' => 'Badger',
      'duck' => 'Duck',
      'alpaca' => 'Alpaca',
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

    $widget_form = new CheckboxWidgetForm($facet);
    $built_form = $widget_form->buildForm($form, $form_state);

    $this->assertInternalType('array', $built_form);
    $this->assertCount(4, $built_form['zoo__animal']['#options']);
    $expected_links = [
      'llama' => 'Llama',
      'badger' => 'Badger',
      'duck' => 'Duck',
      'alpaca' => 'Alpaca',
    ];
    foreach ($expected_links as $index => $value) {
      $this->assertEquals($value, $built_form['zoo__animal']['#options'][$index]);
    }

    // Enable the 'show_numbers' setting again to make sure that the switch
    // between those settings works.
    $facet->setWidgetConfigs(['show_numbers' => 1]);

    $built_form = $widget_form->buildForm($form, $form_state);
    $this->assertInternalType('array', $built_form);
    $this->assertCount(4, $built_form['zoo__animal']['#options']);

    $expected_links = [
      'llama' => 'Llama (10)',
      'badger' => 'Badger (20)',
      'duck' => 'Duck (15)',
      'alpaca' => 'Alpaca (9)',
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

    $form = new CheckboxWidgetForm($facet);

    $this->assertEquals('facets_checkbox_widget', $form->getBaseFormId());
    $this->assertEquals('facets_checkbox_widget__donkey', $form->getFormId());
  }

}
