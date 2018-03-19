<?php

namespace Drupal\social_landing_page\Plugin\Field\FieldWidget;

use Drupal\block_field\Plugin\Field\FieldWidget\BlockFieldWidget as BlockFieldWidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'block_field' widget.
 *
 * @FieldWidget(
 *   id = "block_field_default",
 *   label = @Translation("Block field"),
 *   field_types = {
 *     "block_field"
 *   }
 * )
 */
class BlockFieldWidget extends BlockFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if ($element['#field_parents'][0] != 'field_landing_page_section') {
      return $element;
    }

    $options = [];

    $groups = [
      'Lists (Views)' => $this->t('Basic')->render(),
      'Social Landing Page' => $this->t('Basic')->render(),
    ];

    $blocks = [
      'views_block:activity_stream-block_stream_homepage' => $this->t('New post form and all activities list'),
      'views_block:community_activities-block_stream_landing' => $this->t('Filtered activities list'),
    ];

    foreach ($element['plugin_id']['#options'] as $title => $items) {
      if (isset($groups[$title])) {
        $title = $groups[$title];
      }
      else {
        $title = $this->t('Extra')->render();
      }

      if (!isset($options[$title])) {
        $options[$title] = [];
      }

      foreach ($items as $key => $value) {
        if (isset($blocks[$key])) {
          $value = $blocks[$key];
        }

        $options[$title][$key] = $value;
      }
    }

    foreach ($options as &$option) {
      asort($option);
    }

    $element['plugin_id']['#options'] = $options;

    return $element;
  }

}
