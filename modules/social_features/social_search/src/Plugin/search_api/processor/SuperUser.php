<?php

namespace Drupal\social_search\Plugin\search_api\processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Adds access checks for profiles.
 *
 * @SearchApiProcessor(
 *   id = "super_user",
 *   label = @Translation("Skip User 1"),
 *   description = @Translation("Makes sure that user 1 is not added to the index."),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class SuperUser extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $supported_entity_types = ['profile'];
    foreach ($index->getDatasources() as $datasource) {
      if (in_array($datasource->getEntityTypeId(), $supported_entity_types)) {
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
        if ($object->getOwnerId() == '1') {
          unset($items[$item_id]);
        }
      }
      elseif ($object instanceof UserInterface) {
        if ($object->id() == '1') {
          unset($items[$item_id]);
        }
      }
    }
  }

}
