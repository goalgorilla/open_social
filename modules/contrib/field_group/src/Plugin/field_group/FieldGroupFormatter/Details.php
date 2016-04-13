<?php

/**
/**
 * @file
 * Contains \Drupal\field_group\Plugin\field_group\FieldGroupFormatter\Details.
 */

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Details element.
 *
 * @FieldGroupFormatter(
 *   id = "details",
 *   label = @Translation("Details"),
 *   description = @Translation("Add a details element"),
 *   supported_contexts = {
 *     "form",
 *     "view"
 *   }
 * )
 */
class Details extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {

    $element += array(
      '#type' => 'details',
      '#title' => SafeMarkup::checkPlain($this->t($this->getLabel())),
      '#open' => $this->getSetting('open')
    );

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += array(
        '#attributes' => array('class' => $classes),
      );
    }

    if ($this->getSetting('description')) {
      $element += array(
        '#description' => $this->getSetting('description'),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['open'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display element open by default.'),
      '#default_value' => $this->getSetting('open'),
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
    if ($this->getSetting('open')) {
      $summary[] = $this->t('Default state open');
    }
    else {
      $summary[] = $this->t('Default state closed');
    }

    if ($this->getSetting('required_fields')) {
      $summary[] = $this->t('Mark as required');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = array(
      'open' => FALSE,
      'required_fields' => $context == 'form',
    ) + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

}
