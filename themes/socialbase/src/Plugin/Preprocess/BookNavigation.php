<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;

/**
 * Pre-processes variables for the "book_navigation" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("book_navigation")
 */
class BookNavigation extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // Disables the menu tree below the content on a
    // book node in full display mode.
    $variables['tree'] = '';
  }

}
