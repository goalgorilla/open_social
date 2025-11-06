<?php

declare(strict_types=1);

namespace Drupal\social_user_export\Hooks;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\hux\Attribute\Hook;
use Drupal\user_segments\Entity\UserSegment;

/**
 * Implements hooks for the Social User Export module.
 */
final class SocialUserExportHooks {

  /**
   * Implements hook_entity_operation().
   *
   * Adds CSV export operation to user segment entities.
   * Uses entity access check to ensure the user can export the specific
   * segment (checks both permission and entity state, e.g., whether segment
   * is enabled).
   *
   * User segments are one example of a trigger that provides a list of user
   * IDs. The same export functionality could be added for groups, roles,
   * events, etc. - each would be a different trigger, but all would use the
   * same generic ExportUser action once they have the list of user IDs.
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity): array {
    $operations = [];

    // User segments are one type of trigger that provides a list of user IDs.
    // Once we have the IDs, the export is handled by the ExportUser action.
    // The same pattern could be used for groups, roles, events, etc.
    if (class_exists(UserSegment::class) && $entity instanceof UserSegment) {
      // Check entity access (checks permission AND entity state).
      // This ensures disabled segments don't show the export action.
      $access = $entity->access('export', NULL, TRUE);
      if ($access->isAllowed()) {
        $operations['export_csv'] = [
          'title' => t('Export CSV'),
          'weight' => 15,
          'url' => Url::fromRoute('entity.user_segment.export_csv', [
            'user_segment' => $entity->id(),
          ]),
        ];
      }
    }

    return $operations;
  }

  /**
   * Implements hook_user_segment_access().
   *
   * Controls access to user segment export operation.
   * Checks both permission and entity state (e.g., whether segment is enabled).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The user segment entity.
   * @param string $operation
   *   The operation being performed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  #[Hook('user_segment_access')]
  public function userSegmentAccess(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    // Only handle the 'export' operation.
    if ($operation !== 'export') {
      return AccessResult::neutral();
    }

    // Check if the entity is a UserSegment.
    if (!($entity instanceof UserSegment)) {
      return AccessResult::neutral();
    }

    // Check permission and entity status.
    // UserSegment uses a boolean 'status' field where TRUE means enabled.
    $isEnabled = (bool) $entity->get('status')->value;
    $hasPermission = $account->hasPermission('administer user_segment');

    // Explicitly forbid access if a user doesn't have permission or a segment
    // is disabled. We must use forbidden() instead of neutral() because the
    // default checkAccess() grants access to users with admin_permission, and
    // orIf() would combine neutral + allowed = allowed, which is incorrect.
    if (!$hasPermission || !$isEnabled) {
      return AccessResult::forbidden('Export access denied: user lacks permission or segment is disabled.')
        ->addCacheableDependency($entity);
    }

    // User has permission and segment is enabled.
    return AccessResult::allowed()
      ->addCacheableDependency($entity);
  }

}
