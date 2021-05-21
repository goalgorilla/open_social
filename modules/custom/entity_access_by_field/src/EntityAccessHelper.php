<?php

namespace Drupal\entity_access_by_field;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\Group;
use Drupal\social_event\EventEnrollmentInterface;

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
      if (isset($node->status) && (int) $node->status->value === NodeInterface::NOT_PUBLISHED) {
        $unpublished_own = $account->hasPermission('view own unpublished content');
        if (
          ($node->getOwnerId() !== $account->id() && !$account->hasPermission('administer nodes')) ||
          ($node->getOwnerId() === $account->id() && !$unpublished_own)
        ) {
          return 1;
        }
      }

      $field_definitions = $node->getFieldDefinitions();

      /** @var \Drupal\Core\Field\FieldConfigInterface $field_definition */
      foreach ($field_definitions as $field_name => $field_definition) {
        if ($field_definition->getType() === 'entity_access_field') {
          $field_values = $node->get($field_name)->getValue();

          if (!empty($field_values)) {
            foreach ($field_values as $field_value) {
              if (isset($field_value['value'])) {

                if (in_array($field_value['value'], EntityAccessHelper::getIgnoredValues())) {
                  return 0;
                }

                $permission_label = $field_definition->id() . ':' . $field_value['value'];

                // When content is posted in a group and the account does not
                // have permission we return Access::ignore.
                if ($field_value['value'] === 'group') {
                  // Don't look no further.
                  if ($account->hasPermission('manage all groups')) {
                    return 0;
                  }
                  if (!$account->hasPermission('view ' . $permission_label . ' content')) {
                    // Lets verify if we are a member for flexible groups.
                    $groups = GroupContent::loadByEntity($node);
                    if (!empty($groups)) {
                      $group = reset($groups)->getGroup();
                      if ($group instanceof Group
                        && !$group->getMember($account)
                        && $group->getGroupType()->id() === 'flexible_group') {
                        return 1;
                      }
                    }
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

    $moduleHandler = \Drupal::service('module_handler');
    // If the social_event_invite module is enabled and a person got invited
    // then allow access to view the node.
    // @todo Come up with a better solution for this code.
    if ($moduleHandler->moduleExists('social_event_invite')) {
      if ($op == 'view') {
        $conditions = [
          'field_account' => $account->id(),
          'field_event' => $node->id(),
        ];

        // Load the current Event enrollments so we can check duplicates.
        $storage = \Drupal::entityTypeManager()->getStorage('event_enrollment');
        $enrollments = $storage->loadByProperties($conditions);

        if ($enrollment = array_pop($enrollments)) {
          if ($enrollment->field_request_or_invite_status
            && (int) $enrollment->field_request_or_invite_status->value !== EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED
            && (int) $enrollment->field_request_or_invite_status->value !== EventEnrollmentInterface::INVITE_INVALID_OR_EXPIRED) {
            $access = 2;
          }
        }
      }

    }

    switch ($access) {
      case 2:
        return AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($node);

      case 1:
        return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

}
