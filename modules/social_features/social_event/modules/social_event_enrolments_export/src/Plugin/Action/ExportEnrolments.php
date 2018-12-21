<?php

namespace Drupal\social_event_enrolments_export\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_user_export\Plugin\Action\ExportUser;

/**
 * Exports a event enrollment accounts to CSV.
 *
 * @Action(
 *   id = "social_event_enrolments_export_enrollments_action",
 *   label = @Translation("Export the selected enrollments to CSV"),
 *   type = "profile",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event.views_bulk_operations.confirm",
 * )
 */
class ExportEnrolments extends ExportUser {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    foreach ($entities as &$entity) {
      $entity = $entity->getOwner();
    }

    parent::executeMultiple($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof ProfileInterface) {
      $access = $object->getOwner()->access('view', $account, TRUE);
    }
    else {
      $access = AccessResult::forbidden();
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getFileTemporaryPath() {
    $hash = md5(microtime(TRUE));
    $filename = 'export-enrollments-' . substr($hash, 20, 12) . '.csv';
    return file_directory_temp() . '/' . $filename;
  }

}
