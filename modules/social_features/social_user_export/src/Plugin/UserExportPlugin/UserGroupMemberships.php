<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\group\Entity\Group;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserGroupMemberships' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_group_memberships",
 *  label = @Translation("Group memberships (specified)"),
 *  weight = -198,
 * )
 */
class UserGroupMemberships extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Group memberships (specified)');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    $group_memberships = Group::loadMultiple(social_group_get_all_group_members($entity->id()));
    $groups = [];
    foreach ($group_memberships as $group) {
      $groups[] = "{$group->label()} ({$group->id()})";
    }

    return implode(', ', $groups);
  }

}
