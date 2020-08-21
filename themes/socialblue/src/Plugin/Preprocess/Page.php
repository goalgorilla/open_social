<?php

namespace Drupal\socialblue\Plugin\Preprocess;

use Drupal\socialbase\Plugin\Preprocess\Page as PageBase;

/**
 * Pre-processes variables for the "page" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page")
 */
class Page extends PageBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    if (theme_get_setting('style') === 'sky') {

      // Display merged sidebar on the left side of profile pages, except edit.
      $route_match = \Drupal::routeMatch();
      if ($route_match->getParameter('user') &&
        $route_match->getRouteName() !== 'profile.user_page.single' &&
        $route_match->getRouteName() !== 'entity.user.edit_form') {
        $variables['content_attributes']->addClass('sidebar-left', 'content-merged--sky');
      }

      // Display merged sidebar on the left side of group pages, except edit.
      if ($route_match->getParameter('group') && $route_match->getRouteName() !== 'entity.group.edit_form') {
        $variables['content_attributes']->addClass('sidebar-left', 'content-merged--sky');
      }

      // Add extra class if we have blocks in both complementary regions.
      if ($variables['page']['complementary_top'] && $variables['page']['complementary_bottom']) {
        $variables['content_attributes']->addClass('complementary-both');
      }

    }

  }

}
