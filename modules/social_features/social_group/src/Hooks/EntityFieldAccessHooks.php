<?php

declare(strict_types=1);

namespace Drupal\social_group\Hooks;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\social_group\CurrentGroupProviderInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook implementation for entity field access.
 *
 * Grants group managers the ability to view the user status field on the
 * group manage members page.
 */
final class EntityFieldAccessHooks implements ContainerInjectionInterface {

  /**
   * Constructs the hook handler.
   *
   * @param \Drupal\social_group\CurrentGroupProviderInterface $currentGroupProvider
   *   Provider for the current group from request/route context.
   */
  public function __construct(
    private readonly CurrentGroupProviderInterface $currentGroupProvider,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('social_group.current_group_provider'),
    );
  }

  /**
   * Implements hook_entity_field_access().
   *
   * Grants access to the user status field when the account has the
   * 'administer members' permission in the current group context.
   *
   * @param string $operation
   *   The operation being performed (e.g. 'view', 'edit').
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\FieldItemInterface>|null $items
   *   The field item list.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result indicating whether the access is allowed,
   *   denied, or neutral.
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess(
    string $operation,
    FieldDefinitionInterface $field_definition,
    AccountInterface $account,
    ?FieldItemListInterface $items = NULL,
  ): AccessResultInterface {
    if ($items === NULL) {
      return AccessResult::neutral();
    }

    $user = $items->getEntity();
    if (!$user instanceof UserInterface) {
      return AccessResult::neutral();
    }

    if ($operation !== 'view' || $field_definition->getName() !== 'status') {
      return AccessResult::neutral();
    }

    $group = $this->currentGroupProvider->getCurrentGroup();
    if ($group instanceof GroupInterface
      && $group->hasPermission('administer members', $account)) {
      return AccessResult::allowed()
        ->cachePerUser()
        ->addCacheContexts(['route.group', 'user.group_permissions'])
        ->addCacheableDependency($group);
    }

    return AccessResult::neutral();
  }

}
