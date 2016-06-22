<?php
/**
 * @file
 * Get the context for a given Entity.
 */

namespace Drupal\activity_logger\Service;

use Drupal\Core\Entity\Entity;
use Drupal\group\Entity\GroupContent;

/**
 * Get the context, mostly entity based.
 */
class ContextGetter {

  /**
   * Get the context for a given Entity.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *    Entity object.
   *
   * @return string $context
   *    Context string.
   */
  public function getContext(Entity $entity) {

    // Check if it's placed in a group (regardless off content type).
    if ($group_entity = GroupContent::loadByEntity($entity) || strpos(\Drupal::routeMatch()->getRouteName(), 'entity.group_content') === 0) {
      $context = $this->getFieldValue('group');
    }
    else {
      // Check if it's a post, since only those can be placed on profiles.
      // @TODO: Make this more generic!
      if ($entity->getEntityTypeId() === 'post') {
        if (!empty($entity->get('field_recipient_group')->getValue())) {
          $context = $this->getFieldValue('group');
        }
        elseif (!empty($entity->get('field_recipient_user')->getValue())) {
          $context = $this->getFieldValue('profile');
        }
        else {
          $context = $this->getFieldValue('community');
        }
      }
      else {
        $context = $this->getFieldValue('community');
      }
    }

    return $context;
  }

  /**
   * Get field value of the context field.
   */
  public function getFieldValue($context) {
    // @TODO: Make this more generic, based on the field config.
    $context_values = array(
      'community' => 'community_activity_context',
      'group' => 'group_activity_context',
      'profile' => 'profile_activity_context',
    );

    return $context_values[$context];
  }

}
