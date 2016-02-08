<?php

/**
 * @file
 * Contains \Drupal\social_core\Plugin\Block\SocialPageTitleBlock.
 */

namespace Drupal\social_core\Plugin\Block;

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
    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node) {
      $title = $node->getTitle();
      $author = $node->getRevisionAuthor();
      $author_name = $author->getAccountName();

      return [
        '#theme' => 'page_hero_data',
        '#title' => $title,
        '#author_name' => $author_name,
        '#created_date' => time(),
      ];
    }
    else {
      $request = \Drupal::request();
      if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
        $title = \Drupal::service('title_resolver')->getTitle($request, $route);
      }
      return [
        '#theme' => 'page_hero_data',
        '#title' => $title,
      ];
    }
  }

}
