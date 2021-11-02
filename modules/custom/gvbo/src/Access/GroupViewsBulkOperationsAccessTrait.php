<?php

namespace Drupal\gvbo\Access;

use Drupal\group\Plugin\views\access\GroupPermission;
use Drupal\views\DisplayPluginCollection;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Defines common method for Views Bulk Operations access.
 */
trait GroupViewsBulkOperationsAccessTrait {

  /**
   * Check if View display based on group permission.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param string $display_id
   *   The display ID.
   *
   * @return bool
   *   TRUE if group permission is used.
   */
  public function useGroupPermission(ViewExecutable $view, $display_id) {
    $display_handlers = new DisplayPluginCollection($view, Views::pluginManager('display'));

    if ($display_handlers->has($display_id)) {
      $plugin = $display_handlers->get($display_id)->getPlugin('access');

      if ($plugin instanceof GroupPermission) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
