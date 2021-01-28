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
   * Display merged sidebar on the left side of the following pages...
   */
  const ROUTE_NAMES = [
    // ...profile pages, except edit.
    'user' => [
      'profile.user_page.single',
      'entity.user.edit_form',
    ],
    // ...group pages, except edit and create an album.
    'group' => [
      'entity.group.edit_form',
      'social_album.add',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    if (theme_get_setting('style') !== 'sky') {
      return;
    }

    $route_match = \Drupal::routeMatch();

    foreach (self::ROUTE_NAMES as $parameter_name => $route_names) {
      if (
        $route_match->getParameter($parameter_name) &&
        !in_array($route_match->getRouteName(), $route_names)
      ) {
        $variables['content_attributes']->addClass(
          'sidebar-left',
          'content-merged--sky'
        );

        break;
      }
    }

    // Add extra class if we have blocks in both complementary regions.
    if ($variables['page']['complementary_top'] && $variables['page']['complementary_bottom']) {
      $variables['content_attributes']->addClass('complementary-both');
    }

  }

}
