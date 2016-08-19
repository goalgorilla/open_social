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
      $title = $node->getTitle();
      $author = $node->getOwner();
      $author_name = $author->link();
      $group_link = NULL;

      switch ($node->getType()) {
        case 'topic':
          $topic_type = $node->get('field_topic_type');
          $hero_node = NULL;
          $group_link = $this->getGroupLink($node);
          break;

        case 'event':
          // @todo make link to events overview.
          $topic_type = NULL;
          $hero_node = node_view($node, 'hero');
          $group_link = $this->getGroupLink($node);
          break;

        default:
          $topic_type = NULL;
          $hero_node = NULL;
      }

      return [
        '#theme' => 'page_hero_data',
        '#title' => $title,
        '#author_name' => $author_name,
        '#created_date' => $node->getCreatedTime(),
        '#topic_type' => $topic_type,
        '#group_link' => $group_link,
        '#hero_node' => $hero_node,
        '#section_class' => 'page-title',
      ];
    }
    else {
      $request = \Drupal::request();

      if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $title = \Drupal::service('title_resolver')->getTitle($request, $route);
      }
      return [
        '#type' => 'page_title',
        '#title' => $title,
      ];
    }
  }

  /**
   * Prepare group link when an event or topic belongs to one group.
   */
  protected function getGroupLink($node) {
    $group_link = NULL;
    $group_content = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadByProperties([
        'entity_id' => $node->id(),
        //@TODO: fix this to work with all group types.
        'type' => 'open_group-group_node-' . $node->getType(),
      ]);
    if (!empty($group_content)) {
      $group = reset($group_content)->getGroup();
      $group_link = $group->link();
    }
    return $group_link;
  }

}
