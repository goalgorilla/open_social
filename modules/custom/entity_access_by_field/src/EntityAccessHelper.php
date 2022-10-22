<?php

namespace Drupal\entity_access_by_field;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupContent;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\user\EntityOwnerInterface;

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
   * EntityAccessCheck for given operation, entity and user account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity. Usually one of:
   *   - "view".
   *   - "update".
   *   - "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   * @param string|null $permission
   *   (optional) The admin permission. Defaults to NULL.
   */
  public static function entityAccessCheck(
    EntityInterface $entity,
    string $operation,
    AccountInterface $account,
    string $permission = NULL
  ): int {
    if ($operation !== 'view' || !$entity instanceof EntityOwnerInterface) {
      return self::NEUTRAL;
    }

    // Check published status.
    if (isset($entity->status) && !$entity->status->value) {
      if ($entity->getOwnerId() === $account->id()) {
        if (!$account->hasPermission('view own unpublished content')) {
          return self::FORBIDDEN;
        }
      }
      else {
        if ($permission === NULL) {
          $definition = \Drupal::entityTypeManager()->getDefinition(
            $entity->getEntityTypeId(),
          );

          if ($definition !== NULL) {
            $permission = $definition->getAdminPermission();
          }
        }

        if (
          !empty($permission) &&
          is_string($permission) &&
          !$account->hasPermission($permission)
        ) {
          return self::FORBIDDEN;
        }
      }
    }

    if (!$entity instanceof FieldableEntityInterface) {
      return self::NEUTRAL;
    }

    $field_definitions = $entity->getFieldDefinitions();
    $access = TRUE;

    foreach ($field_definitions as $field_name => $field_definition) {
      if (
        $field_definition->getType() !== 'entity_access_field' ||
        ($field = $entity->get($field_name))->isEmpty()
      ) {
        continue;
      }

      foreach (array_column($field->getValue(), 'value') as $field_value) {
        if (in_array($field_value, EntityAccessHelper::getIgnoredValues())) {
          return self::NEUTRAL;
        }

        $permission = sprintf(
          'view %s.%s.%s:%s content',
          $entity->getEntityTypeId(),
          $entity->bundle(),
          $field_definition->getName(),
          $field_value,
        );

        // When content is posted in a group and the account does not have
        // permission we return Access::ignore.
        if ($field_value === 'group') {
          // Don't look no further.
          if ($account->hasPermission('manage all groups')) {
            return self::NEUTRAL;
          }
          elseif (
            !$account->hasPermission($permission) &&
            $entity instanceof ContentEntityInterface
          ) {
            // If user doesn't have permission we just check user membership in
            // groups where the node attached as group content.
            $group_contents = GroupContent::loadByEntity($entity);

            // Check recursively - if user is a member at least in one group we
            // should allow to check access by gnode module.
            // @see gnode_node_access()
            foreach ($group_contents as $group_content) {
              if ($group_content->getGroup()->getMember($account)) {
                return self::NEUTRAL;
              }
            }
          }
        }

        if (
          $account->hasPermission($permission) ||
          $account->isAuthenticated() &&
          $account->id() === $entity->getOwnerId()
        ) {
          return self::ALLOW;
        }
      }

      $access = FALSE;
    }

    return 1 - (int) $access;
  }

  /**
   * Gets the Entity access for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity. Usually one of:
   *   - "view".
   *   - "update".
   *   - "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   * @param string|null $permission
   *   (optional) The admin permission. Defaults to NULL.
   */
  public static function getEntityAccessResult(
    EntityInterface $entity,
    string $operation,
    AccountInterface $account,
    string $permission = NULL
  ): AccessResultInterface {
    $access = self::entityAccessCheck(
      $entity,
      $operation,
      $account,
      $permission,
    );

    // If the social_event_invite module is enabled and a person got invited
    // then allow access to view the node.
    // @todo Come up with a better solution for this code.
    if (
      \Drupal::moduleHandler()->moduleExists('social_event_invite') &&
      $entity->getEntityTypeId() === 'node' &&
      !$entity->isNew() &&
      $operation === 'view'
    ) {
      $ids = \Drupal::entityQuery('event_enrollment')
        ->accessCheck()
        ->condition('field_account', $account->id())
        ->condition('field_event', $entity->id())
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $enrollment = \Drupal::entityTypeManager()
          ->getStorage('event_enrollment')
          ->load(reset($ids));

        if ($enrollment !== NULL) {
          $status = (int) $enrollment->field_request_or_invite_status->value;

          if (
            $status !== EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED &&
            $status !== EventEnrollmentInterface::INVITE_INVALID_OR_EXPIRED
          ) {
            $access = self::ALLOW;
          }
        }
      }
    }

    switch ($access) {
      case self::ALLOW:
        return AccessResult::allowed()
          ->cachePerPermissions()
          ->addCacheableDependency($entity);

      case self::FORBIDDEN:
        return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

}
