<?php

namespace Drupal\social_follow_tag;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\flag\Entity\Flag;
use Drupal\flag\FlagInterface;
use Drupal\social_tagging\SocialTaggingService;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a custom tagging service.
 */
class SocialFollowTagService extends SocialTaggingService {

  /**
   * Returns a multilevel tree.
   *
   * @param array $terms
   *   An array of items that are selected.
   * @param string $entity_type
   *   The entity type these tags are for.
   *
   * @return array
   *   An hierarchy array of items with their parent.
   */
  public function buildHierarchy(array $terms, $entity_type) {

    $tree = [];

    foreach ($terms as $term) {
      if (!isset($term['target_id'])) {
        continue;
      }

      $current_term = $this->termStorage->load($term['target_id']);
      // Must be a valid Term.
      if (!$current_term instanceof TermInterface) {
        continue;
      }

      // Get current terms parents.
      $parents = $this->termStorage->loadParents($current_term->id());
      if (!empty($parents)) {
        $parent = reset($parents);
        $category = $parent->getName();

        $parameter = 'tag';
        if ($this->allowSplit()) {
          $parameter = social_tagging_to_machine_name($category);
        }

        $route = 'view.search_content.page_no_value';
        if ($entity_type == 'group') {
          $route = 'view.search_groups.page_no_value';
        }


        $flag_link = '';
        $follow = FALSE;
        if (!\Drupal::currentUser()->isAnonymous()) {
          $flag_link_service = \Drupal::service('flag.link_builder');
          $flag_link = $flag_link_service->build($current_term->getEntityTypeId(), $current_term->id(), 'follow_term');

          $flag = Flag::load('follow_term');
          if ($flag instanceof FlagInterface) {
            /** @var \Drupal\flag\FlagService $service */
            $service = \Drupal::service('flag');

            if (!empty($service->getFlagging($flag, $current_term, \Drupal::currentUser()))) {
              $follow = TRUE;
            }
          }
        }

        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['social_tagging' => $current_term->id()]);
        $related_content = [];
        foreach ($nodes as $node) {
          $related_content[$node->bundle()]['label'] = $node->type->entity->label();
          if ($related_content[$node->bundle()]) {
            $related_content[$node->bundle()]['count'] += 1;
            $related_content[$node->bundle()]['nid'][] = $node->id();
          }
        }

        $url = Url::fromRoute($route, [
          $parameter . '[]' => $current_term->id(),
        ]);

        $tree[$parent->id()]['title'] = $category;
        $tree[$parent->id()]['tags'][$current_term->id()] = [
          'url' => $url->toString(),
          'name' => $current_term->getName(),
          'flag' => $flag_link,
          'related_content' => $related_content,
          'follow' => $follow,
        ];
      }
    }
    // Return the tree.
    return $tree;
  }

}
