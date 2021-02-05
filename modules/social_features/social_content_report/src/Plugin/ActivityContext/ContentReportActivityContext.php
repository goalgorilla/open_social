<?php

namespace Drupal\social_content_report\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;

/**
 * Provides a 'ContentReportActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "content_report_activity_context",
 *   label = @Translation("Content report activity context"),
 * )
 */
class ContentReportActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    $ids = $this->entityTypeManager->getStorage('user')->getQuery()
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
   *   A list of Role IDs.
   */
  protected function getRolesWithPermission() {
    $roles_with_perm = [];

    /** @var \Drupal\user\RoleInterface[] $roles */
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();

    // Check for each role which one has permission to "view inappropriate
    // reports".
    foreach ($roles as $role) {
      if ($role->hasPermission('view inappropriate reports')) {
        $roles_with_perm[] = $role->id();
      }
    }

    return $roles_with_perm;
  }

}
