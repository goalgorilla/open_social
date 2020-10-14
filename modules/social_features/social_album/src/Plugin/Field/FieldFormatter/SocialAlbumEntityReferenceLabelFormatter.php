<?php

namespace Drupal\social_album\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'social album entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "social_album_entity_reference_label",
 *   label = @Translation("Album label"),
 *   description = @Translation("Display the label of the referenced album entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SocialAlbumEntityReferenceLabelFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      if (!isset($elements[$delta])) {
        continue;
      }

      $cache = $elements[$delta]['#cache'];

      if (isset($elements[$delta]['#title'])) {
        $elements[$delta] = [
          '#markup' => $this->t('Images posted in album %album', [
            '%album' => Link::fromTextAndUrl($elements[$delta]['#title'], $elements[$delta]['#url'])
              ->toString(),
          ]),
        ];
      }
      else {
        $elements[$delta] = [
          '#plain_text' => $this->t('Images posted in album %album', [
            '%album' => $elements[$delta]['#plain_text'],
          ]),
        ];
      }

      $elements[$delta]['#cache'] = $cache;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();

    return $settings['target_type'] === 'node' &&
      $settings['handler'] === 'views' &&
      $settings['handler_settings']['view']['view_name'] === 'albums';
  }

}
