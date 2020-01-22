<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Alter\AlterInterface;

/**
 * Implements hook_entity_view_alter().
 *
 * @ingroup plugins_alter
 *
 * @BootstrapAlter("entity_view")
 */
class EntityView implements AlterInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(&$build, &$entity = NULL, &$display = NULL) {
    // Extend the core dialog library with our own because we can not do this
    // globally anymore as it broke the layout builder.
    if (isset($build['#attached']['library']) && in_array('core/drupal.dialog.ajax', $build['#attached']['library'], TRUE)) {
      $build['#attached']['library'][] = 'socialbase/modal';
    }
  }

}
