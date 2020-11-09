<?php

namespace Drupal\social_group_flexible_group;

use Drupal\group\Entity\Group;

/**
 * Service class for flexible groups.
 */
class FlexibleGroupHelper {

  /**
   * Gets the highest content visibility value of current group.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group entity.
   *
   * @return string|null
   *   Visibility of group content.
   */
  public function getHighestContentVisibility(Group $group = NULL) {
    $value = NULL;
    // Check if the group entity is provided or not.
    if (empty($group)) {
      $group = _social_group_get_current_group();
    }
    // Either retrieve the value from existing field or get it from new field.
    if ($group !== NULL && $group->hasField('field_group_allowed_visibility')
      && empty($group->get('field_group_content_visibility')->getValue()[0])) {
      // Retrieve allowed options from directly.
      $allowed_options = $group->get('field_group_allowed_visibility')->getValue();
      $option_values = array_column($allowed_options, 'value');
      $value = in_array('public', $option_values) ? 'public' : (in_array('community', $option_values) ? 'community' : (in_array('group', $option_values) ? 'group' : NULL));
    }
    else {
      $value = $group->get('field_group_content_visibility')->getValue()[0]['value'];
    }
    return $value;
  }

}
