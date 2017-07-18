<?php

namespace Drupal\entity_access_by_field;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;

/**
 * Helper class for checking entity access.
 */
class EntityAccessHelper {

  /**
   * Array with values which need to be ignored.
   *
   * @todo Add group to ignored values (when outsider role is working).
   *
   * @return array
   *   An array containing a list of values to ignore.
   */
  public static function getIgnoredValues() {
    return [];
  }

  /**
   * NodeAccessCheck for given operation, node and user account.
   */
  public static function nodeAccessCheck(NodeInterface $node, $op, AccountInterface $account) {
    if ($op === 'view') {
      // Check published status.
      if (isset($node->status) && $node->status->value == NODE_NOT_PUBLISHED) {
        $unpublished_own = $account->hasPermission('view own unpublished content');
        if (($node->getOwnerId() !== $account->id()) || ($node->getOwnerId() === $account->id() && !$unpublished_own)) {
          return 1;
        }
      }

      $field_definitions = $node->getFieldDefinitions();

      /* @var \Drupal\Core\Field\FieldConfigInterface $field_definition */
      foreach ($field_definitions as $field_name => $field_definition) {
        if ($field_definition->getType() === 'entity_access_field') {
          $field_values = $node->get($field_name)->getValue();
          if (!empty($field_values)) {
            foreach ($field_values as $key => $field_value) {
              if (isset($field_value['value'])) {

                if (in_array($field_value['value'], EntityAccessHelper::getIgnoredValues())) {
                  return 0;
                }

                $permission_label = $field_definition->id() . ':' . $field_value['value'];

                // When content is posted in a group and the account does not
                // have permission we return Access::ignore.
                if ($field_value['value'] === 'group') {
                  if (!$account->hasPermission('view ' . $permission_label . ' content')) {
                    return 0;
                  }
                }
                if ($account->hasPermission('view ' . $permission_label . ' content')) {
                  return 2;
                }
                if (($account->id() !== 0) && ($account->id() === $node->getOwnerId())) {
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

  /**
   * Gets the Entity access for the given node.
   */
  public static function getEntityAccessResult(NodeInterface $node, $op, AccountInterface $account) {
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
