<?php

/**
 * @file
 * Hooks provided by the Social Follow Taxonomy Term module.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter array of terms.
 *
 * @param array $term_ids
 *   An array of term ids.
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   Related entity.
 *
 * @return array
 *   Array of term ids.
 */
function hook_social_follow_taxonomy_terms_list_alter(array &$term_ids, EntityInterface $entity) {
  /** @var \Drupal\node\Entity\Node $entity */
  if ($entity instanceof NodeInterface) {
    if ($entity->hasField('field_terms') && !empty($entity->get('field_terms')->getValue())) {
      $terms = $entity->get('field_terms')->getValue();

      foreach ($terms as $term) {
        $term_ids[] = $term['target_id'];
      }
    }
  }

  return $term_ids;
}

/**
 * Provide a method to alter array of related items.
 *
 * @param array $items
 *   The input array of related entities.
 * @param \Drupal\taxonomy\TermInterface $term
 *   Related taxonomy term.
 *
 * @return array
 *   Extended array of related entities.
 */
function hook_social_follow_taxonomy_related_items_alter(array &$items, TermInterface $term) {
  $items = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->getQuery()
    ->condition('field_terms', $term->id())
    ->execute();

  return $items;
}

/**
 * @} End of "addtogroup hooks".
 */
