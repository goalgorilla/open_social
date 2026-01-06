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
      'entity.group.content_translation_add',
      'entity.group_content.create_form',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info): void {
    parent::preprocess($variables, $hook, $info);

    if (theme_get_setting('style') !== 'sky') {
      return;
    }

    $route_match = $this->routeMatch;
    $route_name = $route_match->getRouteName();
    $excluded_routes = self::ROUTE_NAMES;

    \Drupal::moduleHandler()->alter('socialblue_sidebar_left_exceptions', $excluded_routes, $route_match);

    foreach ($excluded_routes as $parameter_name => $route_names) {
      if (
        $route_match->getParameter($parameter_name) &&
        !in_array($route_name, $route_names)
      ) {
        $variables['content_attributes']->addClass(
          'sidebar-left',
          'content-merged--sky'
        );

        break;
      }
    }

    // Add extra class if we have blocks in both complementary regions.
    if (empty($variables['page']['complementary_top']) === FALSE &&
      empty($variables['page']['complementary_bottom']) === FALSE
    ) {
      $variables['content_attributes']->addClass('complementary-both');
    }

    if (theme_get_setting('header_style') === 'two_lines') {
      $variables['multi_line'] = TRUE;
    }
  }

}
