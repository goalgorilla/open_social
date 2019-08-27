<?php

namespace Drupal\socialblue\Plugin\Preprocess;

use Drupal\Core\Template\Attribute;
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

    // Remove complementary class if these regions are empty.
    if (empty($variables['page']['complementary_top']) && empty($variables['page']['complementary_bottom'])) {
      if ($variables['content_attributes'] instanceof Attribute) {
        $variables['content_attributes']->removeClass('layout--with-complementary');
      }
    }

    // Display blocks on the left side of profile pages.
    $route_match = \Drupal::routeMatch();
    if ($route_match->getParameter('user') && $route_match->getRouteName() !== 'entity.profile.type.user_profile_form') {
      $variables['content_attributes']->addClass('sidebar-left');
    }
    // Display blocks on the left side of group pages.
    if ($route_match->getParameter('group') && $route_match->getRouteName() !== 'entity.group.edit_form') {
      $variables['content_attributes']->addClass('sidebar-left');
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
          $variables['content_attributes']->addClass('sidebar-left');
        }
      }
    }

  }

}
