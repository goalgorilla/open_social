<?php

namespace Drupal\social_group\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Social Group routes.
 */
class SocialGroupController extends ControllerBase {

  /**
   * The _title_callback for the view.group_members.page_group_members route.
   *
   * Also for the entity.group_content.collection route.
   *
   * @param object $group
   *   The group ID.
   *
   * @return string
   *   The page title.
   */
  public function groupMembersTitle($group) {
    if (is_object($group)) {
      $group_label = $group->label();
    }
    else {
      $storage = \Drupal::entityTypeManager()->getStorage('group');
      $group_entity = $storage->load($group);
      $group_label = empty($group_entity) ? 'group' : $group_entity->label();
    }
    return $this->t('Members of @name', ['@name' => $group_label]);
  }

  /**
   * The _title_callback for the view.posts.block_stream_group route.
   *
   * @param object $group
   *   The group ID.
   *
   * @return string
   *   The page title.
   */
  public function groupStreamTitle($group) {
    $group_label = $group->label();
    return $group_label;
  }

  /**
   * The title callback for the entity.group_content.add_form.
   *
   * @return string
   *   The page title.
   */
  public function groupAddMemberTitle() {
    return $this->t('Add a member');
  }

}
