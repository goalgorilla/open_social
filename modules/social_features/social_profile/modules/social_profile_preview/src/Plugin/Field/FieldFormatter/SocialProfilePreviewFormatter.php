<?php

namespace Drupal\social_profile_preview\Plugin\Field\FieldFormatter;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'social_profile_preview_username' formatter.
 *
 * @FieldFormatter(
 *   id = "social_profile_preview_username",
 *   label = @Translation("Label"),
 *   description = @Translation("Display entity label even when access is prohibited."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SocialProfilePreviewFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    if ($this->getSetting('link')) {
      foreach (Element::children($elements) as $delta) {
        if (isset($elements[$delta]['#url'])) {
          /** @var \Drupal\Core\Url $url */
          $url = $elements[$delta]['#url'];

          if (!$url->access()) {
            $elements[$delta] = ['#plain_text' => $elements[$delta]['#title']];
          }
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return AccessResult::allowed();
  }

}
