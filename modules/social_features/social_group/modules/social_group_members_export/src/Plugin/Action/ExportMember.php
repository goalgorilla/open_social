<?php

namespace Drupal\social_group_members_export\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_user_export\Plugin\Action\ExportUser;

/**
 * Exports a group member accounts to CSV.
 */
#[Action(
  id: 'social_group_members_export_member_action',
  label: new TranslatableMarkup('Export the selected members to CSV'),
  confirm_form_route_name: 'views_bulk_operations.confirm',
  type: 'group_content',
)]
class ExportMember extends ExportUser {

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration($plugin_id, $entity_id): array {
    $configuration = parent::getPluginConfiguration($plugin_id, $entity_id);

    // Add a group id to the export for making possible to understand
    // the context of plugins executing.
    if (
      !empty($this->view->args) &&
      is_numeric($argument = current($this->view->args))
    ) {
      $configuration['group'] = $argument;
    }

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $entity */
    foreach ($entities as &$entity) {
      $entity = $entity->getEntity();
    }

    return parent::executeMultiple($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof GroupRelationshipInterface && $object->getPluginId() === 'group_membership') {
      $access = $object->getEntity()->access('view', $account, TRUE);
    }
    else {
      $access = AccessResult::forbidden();
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   *
   * To make sure the file can be downloaded, the path must be declared in the
   * download pattern of the social user export module.
   *
   * @see social_user_export_file_download()
   */
  protected function generateFilePath() : string {
    return 'export-members-' . bin2hex(random_bytes(8)) . '.csv';
  }

}
