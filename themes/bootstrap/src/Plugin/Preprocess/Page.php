<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\Page.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\DrupalAttributes;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "page" theme hook.
 *
 * @ingroup theme_preprocess
 *
 * @BootstrapPreprocess("page")
 */
class Page extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables, $hook, array $info) {
    // Setup default attributes.
    $variables->getAttributes(DrupalAttributes::NAVBAR);
    $variables->getAttributes(DrupalAttributes::HEADER);
    $variables->getAttributes(DrupalAttributes::CONTENT);
    $variables->getAttributes(DrupalAttributes::FOOTER);
    $this->preprocessAttributes($variables, $hook, $info);
  }

}
