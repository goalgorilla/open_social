<?php

namespace Drupal\social_group_members_export\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\social_user_export\Plugin\Action\ExportUser;

/**
 * Exports a group member accounts to CSV.
 *
 * @Action(
 *   id = "social_group_members_export_member_action",
 *   label = @Translation("Export the selected members to CSV"),
 *   type = "group_content",
 *   confirm = TRUE,
 * )
 */
class ExportMember extends ExportUser {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    foreach ($entities as &$entity) {
      $entity = $entity->getEntity();
    }

    parent::executeMultiple($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof GroupContentInterface && $object->getContentPlugin()->getPluginId() === 'group_membership') {
      $access = $object->getEntity()->access('view', $account, TRUE);
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
    $filename = 'export-members-' . substr($hash, 20, 12) . '.csv';
    return file_directory_temp() . '/' . $filename;
  }

}
