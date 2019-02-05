<?php

namespace Drupal\social_content_report\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\user\Entity\Role;

/**
 * Provides a 'ContentReportActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "content_report_activity_context",
 *  label = @Translation("Content report activity context"),
 * )
 */
class ContentReportActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // @todo: Dependency injection.
    $ids = \Drupal::service('entity_type.manager')->getStorage('user')->getQuery()
      ->condition('status', 1)
      ->condition('roles', $this->getRolesWithPermission(), 'IN')
      ->execute();

    if (!empty($ids)) {
      // Create a list of recipients in the expected format.
      foreach ($ids as $uid) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $uid,
        ];
      }
    }

    return $recipients;
  }

  /**
   * Returns the role with the required permission.
   *
   * @return array
   */
  protected function getRolesWithPermission() {
    $roles_with_perm = [];
    $roles = Role::loadMultiple();

    // Check for each role which one has permission to "view inappropriate reports".
    foreach ($roles as $role) {
      /* @var \Drupal\user\RoleInterface $role */
      if ($role->hasPermission('view inappropriate reports')) {
        $roles_with_perm[] = $role->id();
      }
    }

    return $roles_with_perm;
  }

}
