<?php

namespace Drupal\social_event_managers\Plugin;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_access_by_field\EntityAccessHelper;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Plugin\GroupContentAccessControlHandler;

/**
 * Provides access control for GroupContent entities and grouped entities.
 */
class VisibilityFieldGroupContentAccessControlHandler extends GroupContentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {

    $groupcontents = GroupContent::loadByEntity($entity);

    $groups = [];
    // Only react if it is actually posted inside a group.
    if (!empty($groupcontents)) {
      foreach ($groupcontents as $groupcontent) {
        /** @var \Drupal\group\Entity\GroupContent $groupcontent */
        $group = $groupcontent->getGroup();
        /** @var \Drupal\group\Entity\Group $group*/
        $groups[] = $group;
      }
    }

    $node = $entity;
    $field_definitions = $node->getFieldDefinitions();

    /** @var \Drupal\Core\Field\FieldConfigInterface $field_definition */
    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_definition->getType() === 'entity_access_field') {
        $field_values = $node->get($field_name)->getValue();

        if (!empty($field_values)) {
          foreach ($field_values as $field_value) {
            if (isset($field_value['value'])) {
              // When content is posted in a group and the account does not
              // have permission we return Access::ignore.
              if ($field_value['value'] === 'group') {
                return AccessResult::forbiddenIf(!$group->hasPermission('view eabf node.event.field_content_visibility:group content', $account));
              }
            }
          }
        }
      }
    }

    return AccessResult::neutral();
  }

}
