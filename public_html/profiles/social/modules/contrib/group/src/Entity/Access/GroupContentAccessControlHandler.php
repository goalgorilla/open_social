<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Access\GroupContentAccessControlHandler
 */

namespace Drupal\group\Entity\Access;

use Drupal\group\Entity\GroupContentType;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Group entity.
 *
 * @see \Drupal\group\Entity\Group.
 */
class GroupContentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    return $entity->getContentPlugin()->checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $group_content_type = GroupContentType::load($entity_bundle);
    return $group_content_type->getContentPlugin()->createAccess($context['group'], $account);
  }

}
