<?php

namespace Drupal\grequest\Plugin\views\field;

use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present an entity link.
 */
abstract class MembershipEntityLink extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $plugin_id = 'group_membership_request';
    /** @var \Drupal\group\Entity\GroupRelationship $group_relationship */
    $group_relationship = $row->_entity;
    $group = $group_relationship->getGroup();
    $link = NULL;

    // Check if plugin exists.
    if (!$group->getGroupType()->hasPlugin($plugin_id)) {
      return $link;
    }
    // Check if current group relationship is type of group_membership_request.
    if ($group_relationship->getPluginId() !== $plugin_id) {
      return $link;
    }

    $user = $group_relationship->getEntity();

    if (!empty($group->getMember($user))) {
      $link = $this->t('Already member');
    }
    elseif ($group_relationship->get(GroupMembershipRequest::STATUS_FIELD)->value === GroupMembershipRequest::REQUEST_PENDING && $group->hasPermission('administer membership requests', $this->currentUser)) {
      $this->options['alter']['query'] = $this->getDestinationArray();
      $link = parent::renderLink($row);
    }

    return $link;
  }

}
