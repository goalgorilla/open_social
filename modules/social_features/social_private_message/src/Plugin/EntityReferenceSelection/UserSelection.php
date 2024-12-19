<?php

namespace Drupal\social_private_message\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\social_profile\Plugin\EntityReferenceSelection\UserSelection as UserSelectionBase;
use Drupal\user\RoleInterface;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "social_private_message:user",
 *   label = @Translation("Social user selection"),
 *   entity_types = {"user"},
 *   group = "social_private_message",
 *   weight = 1
 * )
 */
class UserSelection extends UserSelectionBase {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery(mixed $match = NULL, $match_operator = 'CONTAINS', array $ids = []): QueryInterface {
    /** @var \Drupal\user\RoleStorageInterface $role_storage */
    $role_storage = $this->entityTypeManager->getStorage('user_role');

    // Continue if authenticated users has permission to view private messages.
    $authenticated_role = $role_storage->load(RoleInterface::AUTHENTICATED_ID);
    if ($authenticated_role !== NULL && $authenticated_role->hasPermission('use private messaging system')) {
      return parent::buildEntityQuery($match, $match_operator, $ids);
    }

    // Gets all roles that have permission to view private messages.
    /** @var \Drupal\user\RoleInterface[] $all_roles */
    $all_roles = $role_storage->loadMultiple();
    $rids = array_keys(array_filter($all_roles, static function ($role) {
      return $role->hasPermission('use private messaging system');
    }));

    // Gets users IDs that have permission to view private messages.
    $uids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->condition('roles', $rids, 'IN')
      ->condition('uid', $this->currentUser->id(), '<>')
      ->accessCheck()
      ->execute();

    $query = parent::buildEntityQuery($match, $match_operator, $ids);
    $query->condition('uid', $uids, 'IN');
    return $query;
  }

}
