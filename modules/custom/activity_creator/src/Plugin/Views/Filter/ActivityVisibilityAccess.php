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
    // @TODO Implement visibility access control, see PostVisibilityAccess.
  }

}
