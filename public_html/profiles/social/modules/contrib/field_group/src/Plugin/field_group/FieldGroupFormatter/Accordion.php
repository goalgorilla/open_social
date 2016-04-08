<?php

/**
 * @file
 * Contains \Drupal\field_group\Plugin\field_group\FieldGroupFormatter\Accordion.
 */

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormState;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'accordion' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "accordion",
 *   label = @Translation("Accordion"),
 *   description = @Translation("This fieldgroup renders child groups as jQuery accordion."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class Accordion extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {

    $form_state = new FormState();

    $element += array(
      '#type' => 'field_group_accordion',
      '#effect' => $this->getSetting('effect'),
    );

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += array('#attributes' => array('class' => $classes));
    }

    \Drupal\field_group\Element\Accordion::processAccordion($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $form['effect'] = array(
      '#title' => $this->t('Effect'),
      '#type' => 'select',
      '#options' => array('none' => $this->t('None'), 'bounceslide' => $this->t('Bounce slide')),
      '#default_value' => $this->getSetting('effect'),
      '#weight' => 2,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = array();
    $summary[] = $this->t('Effect : @effect',
      array('@effect' => $this->getSetting('effect'))
    );

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    return array(
      'effect' => 'none',
    ) + parent::defaultSettings($context);
  }

}
