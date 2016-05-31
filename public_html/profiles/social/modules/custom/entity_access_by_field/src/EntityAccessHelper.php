<?php
/**
 * EntityAccessHelper
 */

namespace Drupal\entity_access_by_field;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;


class EntityAccessHelper {

  static function nodeAccessCheck(NodeInterface $node, $op, AccountInterface $account) {
    if ($op === 'view') {
      $field_definitions = $node->getFieldDefinitions();

      /* @var \Drupal\Core\Field\FieldConfigInterface $field_definition */
      foreach ($field_definitions as $field_name => $field_definition) {
        if ($field_definition->getType() === 'entity_access_field') {
          $field_values = $node->get($field_name)->getValue();
          if (!empty($field_values)) {
            foreach ($field_values as $key => $field_value) {
              if (isset($field_value['value'])) {
                $permission_label = $field_definition->id() . ':' . $field_value['value'];
                if ($account->hasPermission('view ' . $permission_label . ' content', $account)) {
                  return 2;
                }
              }
            }
          }
          $access = FALSE;
        }
      }
      if (isset($access) && $access === FALSE) {
        return 1;
      }
    }
    return 0;
  }

  static function getEntityAccessResult(NodeInterface $node, $op, AccountInterface $account) {
    $access = EntityAccessHelper::nodeAccessCheck($node, $op, $account);

    switch ($access) {
      case 2:
        return AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($node);

      case 1:
        return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }
}
