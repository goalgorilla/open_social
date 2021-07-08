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
   * Neutral status.
   */
  const NEUTRAL = 0;

  /**
   * Forbidden status.
   */
  const FORBIDDEN = 1;

  /**
   * Allowed status.
   */
  const ALLOW = 2;

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
          return EntityAccessHelper::FORBIDDEN;
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
                  return EntityAccessHelper::NEUTRAL;
                }

                $permission_label = $field_definition->id() . ':' . $field_value['value'];

                // When content is posted in a group and the account does not
                // have permission we return Access::ignore.
                if ($field_value['value'] === 'group') {
                  // Don't look no further.
                  if ($account->hasPermission('manage all groups')) {
                    return EntityAccessHelper::NEUTRAL;
                  }
                  elseif (!$account->hasPermission('view ' . $permission_label . ' content')) {
                    // If user doesn't have permission we just check user
                    // membership in groups where the node attached as
                    // group content.
                    $group_contents = GroupContent::loadByEntity($node);
                    // Check recursively - if user is a member at least in one
                    // group we should allow to check access by gnode module.
                    /* @see gnode_node_access() */
                    foreach ($group_contents as $group_content) {
                      $group = $group_content->getGroup();
                      if ($group instanceof Group && $group->getMember($account)) {
                        return EntityAccessHelper::NEUTRAL;
                      }
                    }
                  }
                }
                if ($account->hasPermission('view ' . $permission_label . ' content')) {
                  return EntityAccessHelper::ALLOW;
                }
                if (($account->id() !== 0) && ($account->id() === $node->getOwnerId())) {
                  return EntityAccessHelper::ALLOW;
                }

              }
            }
          }
          $access = FALSE;
        }
      }
      if (isset($access) && $access === FALSE) {
        return EntityAccessHelper::FORBIDDEN;
      }
    }
    return EntityAccessHelper::NEUTRAL;
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
            $access = EntityAccessHelper::ALLOW;
          }
        }
      }
    }

    switch ($access) {
      case EntityAccessHelper::ALLOW:
        return AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($node);

      case EntityAccessHelper::FORBIDDEN:
        return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

}
