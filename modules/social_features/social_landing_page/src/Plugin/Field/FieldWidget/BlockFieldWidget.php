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
      $this->t('Lists (Views)')->render() => $this->t('Basic'),
      $this->t('Social Landing Page')->render() => $this->t('Basic'),
    ];

    $blocks = [
      'activity_overview_block' => $this->t('Community statistics'),
      'views_block:activity_stream-block_stream_homepage' => $this->t('Personalised activity stream'),
      'views_block:community_activities-block_stream_landing' => $this->t('Complete community activity stream'),
      'views_block:latest_topics-block_latest_topics' => $this->t('Latest topics'),
      'views_block:newest_groups-block_newest_groups' => $this->t('Newest groups'),
      'views_block:newest_users-block_newest_users' => $this->t('Newest users'),
      'views_block:upcoming_events-block_community_events' => $this->t('Upcoming community events'),
    ];

    foreach ($element['plugin_id']['#options'] as $title => $items) {
      if (isset($groups[$title])) {
        $title = $groups[$title];
      }
      else {
        $title = $this->t('Extra');
      }

      $title = $title->render();

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
