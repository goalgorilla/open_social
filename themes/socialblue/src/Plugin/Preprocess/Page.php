<?php

namespace Drupal\socialblue\Plugin\Preprocess;

use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
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

    $style = theme_get_setting('style');
    if ($style && $style === 'sky') {
      // In most cases secondary_navigation is hidden.
      $variables['display_secondary_navigation'] = FALSE;

      // Display blocks on the left side of profile pages.
      $route_match = \Drupal::routeMatch();
      if ($route_match->getParameter('user') && $route_match->getRouteName() !== 'entity.profile.type.user_profile_form') {
        $variables['content_attributes']->addClass('sidebar-left');
        $variables['display_secondary_navigation'] = TRUE;

        if ($route_match->getRouteName() == 'view.user_information.user_information') {
          $variables['content_attributes']->addClass('content-merged');
        }
      }

      // Display blocks on the left side of group pages.
      if ($route_match->getParameter('group') && $route_match->getRouteName() !== 'entity.group.edit_form') {
        $variables['content_attributes']->addClass('sidebar-left');
        $variables['display_secondary_navigation'] = TRUE;

        if ($route_match->getRouteName() == 'view.group_information.page_group_about') {
          $variables['content_attributes']->addClass('content-merged');
        }
        $group = \Drupal::service('current_route_match')->getParameter('group');
        if ($group instanceof GroupInterface && in_array($group->bundle(), ['challenge', 'cc'])) {
          $variables['content_attributes']->removeClass('content-merged');
        }
      }
      if ($route_match->getParameter('node')) {
        $variables['display_secondary_navigation'] = TRUE;
      }

      // @TODO: Move this code to course module.
      // Display blocks on the left side of course pages.
      if (\Drupal::service('module_handler')->moduleExists('social_course')) {
        if ($route_match->getParameter('node')) {
          $node = \Drupal::service('current_route_match')->getParameter('node');
          if (!is_null($node) && (!$node instanceof Node)) {
            $node = Node::load($node);
          }
          $course_types = social_course_get_material_types();
          if (in_array($node->getType(), $course_types)) {
            $variables['content_attributes']->addClass(['sidebar-left', 'content-merged']);
          }
        }
      }
    }

  }

}
