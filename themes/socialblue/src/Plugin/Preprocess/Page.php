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

    $style = theme_get_setting('style');
    if ($style && $style === 'sky') {

      // Display sidebar on the left side of profile pages, except edit.
      $route_match = \Drupal::routeMatch();
      if ($route_match->getParameter('user') && $route_match->getRouteName() !== 'entity.profile.type.user_profile_form') {
        $variables['content_attributes']->addClass('sidebar-left');
        // Visually merge content in body card in and sidebar blocks.
        if ($route_match->getRouteName() === 'view.user_information.user_information') {
          $variables['content_attributes']->addClass('content-merged');
        }
      }

      // Display sidebar on the left side of group pages, except edit.
      if ($route_match->getParameter('group') && $route_match->getRouteName() !== 'entity.group.edit_form') {
        $variables['content_attributes']->addClass('sidebar-left');
        // Visually merge content in body card in and sidebar blocks.
        if ($route_match->getRouteName() === 'view.group_information.page_group_about') {
          $variables['content_attributes']->addClass('content-merged');
        }
      }

    }

  }

}
