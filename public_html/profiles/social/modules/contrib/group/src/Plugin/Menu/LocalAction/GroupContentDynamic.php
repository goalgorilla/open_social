<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\Menu\LocalAction\GroupContentDynamic.
 */

namespace Drupal\group\Plugin\Menu\LocalAction;

use Drupal\Core\Url;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Adds a redirect to the original page to group content local actions.
 */
class GroupContentDynamic extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    $options['query']['destination'] = Url::fromRoute('<current>')->toString();
    return $options;
  }

}
