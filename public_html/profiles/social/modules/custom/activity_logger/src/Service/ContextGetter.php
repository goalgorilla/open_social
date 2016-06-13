<?php

namespace Drupal\activity_logger\Service;

use Drupal\Core\Entity\Entity;
use Drupal\group\Entity\GroupContent;

class ContextGetter {

  public function getContext(Entity $entity) {

    // Check if it's placed in a group (regardless off content type).
    if ($group_entity = GroupContent::loadByEntity($entity)) {
      $context = $this->getFieldValue('group');
    }
    else {
      // Check if it's a post, since only those can be placed on profiles.
      // @TODO: Make this more generic!
      if ($entity->getEntityTypeId() === 'post') {
        if (!empty($entity->get('field_recipient_user')->getValue())) {
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

  public function getFieldValue($context) {
    // @TODO: Make this more generic, based on the field config.
    $contextValues = array (
      'community' => 'community_activity_context',
      'group' => 'group_activity_context',
      'profile' => 'profile_activity_context',
    );

    return $contextValues[$context];
  }
}
