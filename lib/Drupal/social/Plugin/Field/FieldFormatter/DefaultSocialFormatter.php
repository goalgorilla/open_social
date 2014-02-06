<?php

/**
 * @file
 * Contains \Drupal\social\Plugin\field\formatter\DefaultSocialFormatter.
 */

namespace Drupal\social\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Parent plugin for social formatters
 */
abstract class DefaultSocialFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements['count'] = array(
      '#type' => 'textfield',
      '#title' => t('Count'),
      '#default_value' => $this->getSetting('count'),
      '#description' => t('Count of items to show. Input 0 to display all.'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $count = $this->getSetting('count');
    if (!empty($count)) {
      $summary[] = t('Display: @count items', array('@count' => $count));
    }
    else {
      $summary[] = t('Display all items');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    $entity = $items->getEntity();

    $this->bundle = $this->fieldDefinition->bundle;
    $this->entity_type = $this->fieldDefinition->entity_type;
    $this->id = $entity->id();
    $this->max_items = $this->getSetting('count');

    foreach ($items as $delta => $item) {
      $output = $this->socialCommentRender($item->url);
      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

  /**
   * Formats a social comment.
   *
   * @param string $url
   *   The URL value.
   *
   * @return string
   *   The formatted social comment.
   */
  abstract protected function socialCommentRender($url);
}
