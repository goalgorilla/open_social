<?php

namespace Drupal\social_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "link_with_class",
 *   label = @Translation("Link with Custom Class"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkClassFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() +
      ['class' => ''];

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['class'] = [
      '#type' => 'textfield',
      '#title' => t('Class on Link'),
      '#default_value' => $this->getSetting('class'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = parent::settingsSummary();

    $settings = $this->getSettings();

    if (!empty($settings['class'])) {
      $summary[] = t('Class(es) on button = "@classes"', ['@classes' => $settings['class']]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildUrl(LinkItemInterface $item) {
    $url = parent::buildUrl($item);

    $settings = $this->getSettings();

    if (!empty($settings['class'])) {
      $options = $url->getOptions();
      $options['attributes']['class'] = $settings['class'];
      $url->setOptions($options);
    }

    return $url;
  }

}
