<?php

declare(strict_types=1);

namespace Drupal\social_comment\Access;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccessPolicyBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\RefinableCalculatedPermissionsInterface;
use Drupal\social_comment\Cache\HiddenCommentFieldMapCache;

/**
 * Grants hidden comment field view access to node owners.
 */
final class HiddenCommentNodeOwnerAccessPolicy extends AccessPolicyBase {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected HiddenCommentFieldMapCache $hiddenCommentFieldMapCache,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(string $scope): bool {
    return $scope === HiddenCommentFieldAccessPolicy::SCOPE_HIDDEN_COMMENT_FIELD;
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, string $scope): RefinableCalculatedPermissionsInterface {
    $calculatedPermissions = parent::calculatePermissions($account, $scope);

    if ($account->isAnonymous()) {
      return $calculatedPermissions;
    }

    $hidden_map = $this->hiddenCommentFieldMapCache->getMap();
    if ($hidden_map === []) {
      return $calculatedPermissions;
    }

    $owned_nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $account->id())
      ->condition('nid', array_keys($hidden_map), 'IN')
      ->execute();

    foreach ($owned_nids as $nid) {
      $nid = (int) $nid;
      foreach ($hidden_map[$nid] as $field_name) {
        $cacheability = new CacheableMetadata();
        $cacheability->addCacheContexts(['user']);
        $cacheability->addCacheTags(['node:' . $nid]);

        $calculatedPermissions->addItem(
          new CalculatedPermissionsItem(
            permissions: [HiddenCommentFieldAccessPolicy::PERMISSION_VIEW_HIDDEN],
            isAdmin: FALSE,
            scope: HiddenCommentFieldAccessPolicy::SCOPE_HIDDEN_COMMENT_FIELD,
            identifier: HiddenCommentFieldAccessPolicy::identifier($nid, $field_name),
          )
        )->addCacheableDependency($cacheability);
      }
    }

    return $calculatedPermissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts(): array {
    return ['user'];
  }

}
