<?php

namespace Drupal\social_content_report\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\flag\FlagInterface;

/**
 * Class FlagAccessCheck.
 */
class FlagAccessCheck implements AccessInterface {

  /**
   * Checks if user is allowed to flag.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag type.
   * @param int $entity_id
   *   The entity ID which is being reported.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed if user may use the flag and hasn't reported it yet.
   */
  public function access(AccountInterface $account, FlagInterface $flag, $entity_id) {
    if (strpos($flag->id(), 'report_') === 0) {
      // Make sure user is allowed to use the flag.
      if (!$account->hasPermission('flag ' . $flag->id())) {
        return AccessResult::forbidden();
      }

      // @todo Implement Dependency Injection.
      $entity = \Drupal::service('entity_type.manager')->getStorage($flag->getFlaggableEntityTypeId())->load($entity_id);
      $flagged = $flag->isFlagged($entity, $account);

      // If the user already flagged the content they aren't allowed to do it
      // again.
      if ($flagged) {
        return AccessResult::forbidden();
      }
      else {
        return AccessResult::allowed();
      }
    }
    return AccessResult::neutral();
  }

}
