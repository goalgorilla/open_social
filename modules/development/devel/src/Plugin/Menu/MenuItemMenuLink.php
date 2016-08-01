<?php

/**
 * @file
 * Contains \Drupal\devel\Plugin\Menu\MenuItemMenuLink.
 */

namespace Drupal\devel\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Url;

/**
 * Modifies the menu link to add current route path.
 */
class MenuItemMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $options = parent::getOptions();
    $options['query']['path'] = '/' . Url::fromRoute('<current>')->getInternalPath();
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
