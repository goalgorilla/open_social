<?php

/**
 * @file
 * Contains \Drupal\webprofiler\Theme\ThemeNegotiatorWrapper.
 */

namespace Drupal\webprofiler\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiator;

/**
 * Class ThemeNegotiatorWrapper
 */
class ThemeNegotiatorWrapper extends ThemeNegotiator {

  /**
   * @var \Drupal\Core\Theme\ThemeNegotiatorInterface
   */
  private $negotiator;

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    foreach ($this->getSortedNegotiators() as $negotiator) {
      if ($negotiator->applies($route_match)) {
        $theme = $negotiator->determineActiveTheme($route_match);
        if ($theme !== NULL && $this->themeAccess->checkAccess($theme)) {
          $this->negotiator = $negotiator;
          return $theme;
        }
      }
    }
  }

  /**
   * @return \Drupal\Core\Theme\ThemeNegotiatorInterface
   */
  public function getNegotiator() {
    return $this->negotiator;
  }
}
