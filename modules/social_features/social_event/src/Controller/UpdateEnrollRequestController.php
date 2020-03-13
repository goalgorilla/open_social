<?php


namespace Drupal\social_event\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Updates a pending enrollment request.
 *
 * @package Drupal\social_event\Controller
 */
class UpdateEnrollRequestController {

  /**
   * Updates the enrollment request.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param bool $approveEnrollment
   */
  public function updateEnrollmentRequest(EntityInterface $entity, $approveEnrollment) {
    if ($entity) {

    }
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowed();
  }

}
