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
  protected function checkAccess(EntityInterface $entity) {
    return AccessResult::allowed();
  }

}
