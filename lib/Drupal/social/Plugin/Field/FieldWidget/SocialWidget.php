<?php

/**
 * @file
 * Contains \Drupal\social\Plugin\Field\FieldWidget\SocialWidget.
 */

namespace Drupal\social\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Plugin implementation of the 'social' widget.
 *
 * @FieldWidget(
 *   id = "social",
 *   label = @Translation("Social field"),
 *   field_types = {
 *     "social_google",
 *     "social_facebook"
 *   },
 *   settings = {
 *     "placeholder" = ""
 *   }
 * )
 */
class SocialWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element['url'] = array(
      '#type' => 'url',
      '#title' => t('URL'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#default_value' => isset($items[$delta]->url) ? $items[$delta]->url : NULL,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder for URL'),
      '#default_value' => $this->getSetting('placeholder_url'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }
    else {
      $summary[] = t('No placeholder');
    }

    return $summary;
  }
}
