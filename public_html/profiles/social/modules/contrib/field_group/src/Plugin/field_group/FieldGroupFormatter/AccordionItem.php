<?php

/**
 * @file
 * Contains \Drupal\field_group\Plugin\field_group\FieldGroupFormatter\AccordionItem.
 */

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'accordion_item' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "accordion_item",
 *   label = @Translation("Accordion Item"),
 *   description = @Translation("This fieldgroup renders the content in a div, part of accordion group."),
 *   format_types = {
 *     "open",
 *     "closed",
 *   },
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   },
 * )
 */
class AccordionItem extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {

    $element += array(
      '#type' => 'field_group_accordion_item',
      '#collapsed' => $this->getSetting('formatter'),
      '#description' => $this->getSetting('description'),
      '#title' => Drupal::translation()->translate($this->getLabel()),
    );

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += array('#attributes' => array('class' => $classes));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $form['formatter'] = array(
      '#title' => $this->t('Default state'),
      '#type' => 'select',
      '#options' => array_combine($this->pluginDefinition['format_types'], $this->pluginDefinition['format_types']),
      '#default_value' => $this->getSetting('formatter'),
      '#weight' => -4,
    );

    if ($this->context == 'form') {
      $form['required_fields'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Mark group as required if it contains required fields.'),
        '#default_value' => $this->getSetting('required_fields'),
        '#weight' => 2,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = array();

    if ($this->getSetting('required_fields')) {
      $summary[] = $this->t('Mark as required');
    }

    if ($this->getSetting('description')) {
      $summary[] = $this->t('Description : @description',
        array('@description' => $this->getSetting('description'))
      );
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = array(
      'formatter' => 'closed',
      'description' => '',
    ) + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

}