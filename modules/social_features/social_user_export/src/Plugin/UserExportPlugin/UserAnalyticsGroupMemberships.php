<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserAnalyticsGroupMemberships' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_group_memberships_count",
 *  label = @Translation("Group memberships"),
 *  weight = -199,
 * )
 */
class UserAnalyticsGroupMemberships extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Group memberships');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    return count(social_group_get_all_group_members($entity->id()));
  }

}
