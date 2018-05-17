<?php

namespace Drupal\social_search\Plugin\search_api\processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;
use Drupal\search_api\Query\QueryInterface;

/**
 * Ignores blocked users in index and queries.
 *
 * Makes sure blocked users are no longer indexed AND
 * also filters out blocked users from queries.
 *
 * @SearchApiProcessor(
 *   id = "blocked_users",
 *   label = @Translation("Skip Blocked users"),
 *   description = @Translation("Makes sure that blocked users are not added to the index."),
 *   stages = {
 *     "alter_items" = 0,
 *     "preprocess_query" = -30,
 *   },
 * )
 */
class BlockedUsers extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $entity_types = ['profile'];
    foreach ($index->getDatasources() as $datasource) {
      if (in_array($datasource->getEntityTypeId(), $entity_types)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      if ($object instanceof ProfileInterface) {
        // Profile ownedId is the userId.
        if ($object->getOwner()->isBlocked() === TRUE) {
          unset($items[$item_id]);
        }
      }
      elseif ($object instanceof UserInterface) {
        if ($object->isBlocked() === TRUE) {
          unset($items[$item_id]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    if (!$query->getOption('search_api_bypass_access')) {
      // Add a condition to filter out users through the status field.
      $conditions = $query->createConditionGroup('AND');
      $conditions->addCondition('user_status', 0, '<>');
      // Add the conditions.
      $query->addConditionGroup($conditions);
    }
  }

}
