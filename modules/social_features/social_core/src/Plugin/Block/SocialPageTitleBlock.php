<?php

namespace Drupal\social_core\Plugin\Block;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Block\Plugin\Block\PageTitleBlock;

/**
 * Provides a 'SocialPageTitleBlock' block.
 *
 * @Block(
 *  id = "social_page_title_block",
 *  admin_label = @Translation("Page title block"),
 * )
 */
class SocialPageTitleBlock extends PageTitleBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Take the raw parameter. We'll load it ourselves.
    $nid = \Drupal::routeMatch()->getRawParameter('node');
    $node = FALSE;

    // At this point the parameter could also be a simple string of a nid.
    // EG: on: /node/%node/enrollments.
    if (!is_null($nid) && !is_object($nid)) {
      $node = Node::load($nid);
    }

    if ($node) {
      $translation = \Drupal::service('entity.repository')->getTranslationFromContext($node);

      if (!empty($translation)) {
        $node->setTitle($translation->getTitle());
      }
      $title = $node->getTitle();
      $group_link = NULL;

      return [
        '#theme' => 'page_hero_data',
        '#title' => $title,
        '#node' => $node,
        '#section_class' => 'page-title',
      ];
    }
    else {
      $request = \Drupal::request();

      if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $title = \Drupal::service('title_resolver')->getTitle($request, $route);

        return [
          '#type' => 'page_title',
          '#title' => $title,
        ];
      }
      else {
        return [
          '#type' => 'page_title',
          '#title' => '',
        ];
      }

    }
  }
}
