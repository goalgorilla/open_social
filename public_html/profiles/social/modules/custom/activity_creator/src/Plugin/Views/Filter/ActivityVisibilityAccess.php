<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\views\filter\ActivityVisibilityAccess.
 */

namespace Drupal\activity_creator\Plugin\Views\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters activity based on visibility settings.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("activity_visibility_access")
 */
class ActivityVisibilityAccess extends FilterPluginBase {

  public function adminSummary() {
  }

  protected function operatorForm(&$form, FormStateInterface $form_state) {
  }

  public function canExpose() {
    return FALSE;
  }

  /**
   * Currently use similar access as for the entity.
   *
   * Probably want to extend this to entity access based on the node grant
   * system when this is implemented.
   * See https://www.drupal.org/node/777578
   */
  public function query() {

    // We have a few scenarios:
    // 1. There is a recipient user and destination is only notification,
    //   if current user is not recipient always deny access.
    // 2. There is a recipient group:
    //   Check if the user has access to the related entity.
    // 3. There is a related entity of type node
    //   Check if user has access to the related entity,
    //   use node_access_grants system.
    //   views filter content access with relationship to node
    // 4. There is a related entity of type post
    //   Check if user has access to the post, we can use PostVisibilityAccess.

    // Note: in future we should implement entity grant system instead!
    // to support other entities as well.

  }

}
