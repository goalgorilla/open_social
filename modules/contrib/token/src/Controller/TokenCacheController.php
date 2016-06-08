<?php

/**
 * @file
 * Contains \Drupal\token\Controller\TokenCacheController.
 */

namespace Drupal\token\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Clears cache for tokens.
 */
class TokenCacheController extends ControllerBase  {

  /**
   * Clear caches and redirect back to the frontpage.
   */
  public function flush() {
    token_clear_cache();
    drupal_set_message(t('Token registry caches cleared.'));
    return $this->redirect('<front>');
  }

}
