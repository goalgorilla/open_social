<?php

/**
 * @file
 * Contains \Drupal\token_module_test\Controller\TokenTreeBrowseController.
 */

namespace Drupal\token_module_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class TokenTreeBrowseController extends ControllerBase {

  /**
   * Page callback to output a link.
   */
  function outputLink(Request $request) {
    $build['#title'] = $this->t('Available tokens');
    $build['tree']['#theme'] = 'token_tree_link';
    return $build;
  }
}
