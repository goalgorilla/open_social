<?php

namespace Drupal\entity_access_by_field;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupContent;
use Drupal\node\NodeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Helper class for checking entity access.
 */
class EntityAccessHelper implements EntityAccessHelperInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new EntityAccessHelper instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Array with values which need to be ignored.
   *
   * @deprecated in social:11.4.2 and is removed from social:12.0.0.
   */
  public static function getIgnoredValues(): array {
    return [];
  }

  /**
   * NodeAccessCheck for given operation, node and user account.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity. Usually one of:
   *   - "view"
   *   - "update"
   *   - "delete"
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @deprecated in social:11.4.2 and is removed from social:12.0.0. Use
   *   process instead.
   */
  public static function nodeAccessCheck(
    NodeInterface $node,
    string $operation,
    AccountInterface $account
  ): int {
    return \Drupal::classResolver(__CLASS__)->process($node, $operation, $account);
  }

  /**
   * NodeAccessCheck for given operation, node and user account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity. Usually one of:
   *   - "view"
   *   - "update"
   *   - "delete"
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   */
  protected function process(
    EntityInterface $entity,
    string $operation,
    AccountInterface $account
  ): int {
    if ($operation !== 'view' || !$entity instanceof EntityOwnerInterface) {
      return self::NEUTRAL;
    }

    // Check published status.
    if ($entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
      if ($entity->getOwnerId() === $account->id()) {
        if (!$account->hasPermission('view own unpublished content')) {
          return self::FORBIDDEN;
        }
      }
      else {
        $definition = $this->entityTypeManager->getDefinition(
          $entity->getEntityTypeId(),
        );

        if (
          $definition !== NULL &&
          is_string($permission = $definition->getAdminPermission()) &&
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

    /** @var \Drupal\Core\Field\FieldConfigInterface $field_definition */
    foreach ($field_definitions as $field_name => $field_definition) {
      if (
        $field_definition->getType() !== 'entity_access_field' ||
        ($field = $entity->get($field_name))->isEmpty()
      ) {
        continue;
      }

      foreach (array_column($field->getValue(), 'value') as $field_value) {
        $permission = sprintf(
          'view %s.%s.%s:%s content',
          $entity->getEntityTypeId(),
          $entity->bundle(),
          $field_name,
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
   * Gets the Entity access for the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check access to.
   * @param string $operation
   *   The operation that is to be performed on $entity. Usually one of:
   *   - "view"
   *   - "update"
   *   - "delete"
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @deprecated in social:11.4.2 and is removed from social:12.0.0. Use check
   *   instead.
   */
  public static function getEntityAccessResult(
    NodeInterface $node,
    string $operation,
    AccountInterface $account
  ): AccessResultInterface {
    return \Drupal::classResolver(__CLASS__)->check($node, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function check(
    EntityInterface $entity,
    string $operation,
    AccountInterface $account
  ): AccessResultInterface {
    switch ($this->process($entity, $operation, $account)) {
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
