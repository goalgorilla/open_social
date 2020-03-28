<?php

namespace Drupal\social_group\Plugin\views\field;

use Drupal\social_group\Entity\Group;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\group\Entity\GroupContent;

/**
 * Field handler to present the groups membership count.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("social_group_membership_count")
 */
class SocialGroupMembershipCount extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $group_content = $this->getEntity($row);
    // Grab the group from the group_invite
    if ($group_content instanceof GroupContent) {
      $group = $group_content->getGroup();
      if ($group instanceof Group) {
        /** @var \Drupal\social_group\GroupStatistics $group_statistics */
        $group_statistics = \Drupal::service('social_group.group_statistics');
        // return the group member count.
        return $group_statistics->getGroupMemberCount($group);
      }
    }
  }

}
