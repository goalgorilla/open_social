<?php

namespace Drupal\alternative_frontpage;

use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Url;

/**
 * Replace the isFrontPage method to include our own logic.
 *
 * This is needed so the language switcher will see our homepages as <front>
 * and create menu links without the full url in the link.
 */
class AlternativeFrontpagePathMatcher extends PathMatcher {

  /**
   * {@inheritdoc}
   */
  public function isFrontPage() {
    // Cache the result as this is called often.
    if (!isset($this->isCurrentFrontPage)) {
      $this->isCurrentFrontPage = FALSE;
      // Ensure that the code can also be executed when there is no active
      // route match, like on exception responses.
      if ($this->routeMatch->getRouteName()) {
        $url = Url::fromRouteMatch($this->routeMatch);
        $this->isCurrentFrontPage = ($url->getRouteName() && '/' . $url->getInternalPath() === $this->getFrontPagePath());
      }

      // If the alternative homepage has been set, also returns it as <front>.
      $frontpage_config = \Drupal::service('alternative_frontpage.redirect_homepage');
      $frontpage_an = $frontpage_config->getConfigUrlPath('anonymous');
      $frontpage_lu = $frontpage_config->getConfigUrlPath('authenticated');
      $current_path = \Drupal::service('path.current')->getPath();

      if (($frontpage_an && $current_path === $frontpage_an) || ($frontpage_lu && $current_path === $frontpage_lu)) {
        $this->isCurrentFrontPage = TRUE;
      }
    }

    return $this->isCurrentFrontPage;
  }

}
