<?php

namespace Drupal\social_search\Plugin\search_api\processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_user\Service\SocialUserHelper;
use Drupal\user\UserInterface;

/**
 * Ignores not Verified users in index.
 *
 * Makes sure not Verified users are no longer indexed.
 *
 * @SearchApiProcessor(
 *   id = "verified_users",
 *   label = @Translation("Skip not Verified users"),
 *   description = @Translation("Makes sure that only Verified users are added to the index."),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class VerifiedUsers extends ProcessorPluginBase {

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
        // Profile owner ID is the user ID.
        if (!SocialUserHelper::isVerifiedUser($object->getOwner())) {
          unset($items[$item_id]);
        }
      }
      elseif ($object instanceof UserInterface) {
        if (!SocialUserHelper::isVerifiedUser($object)) {
          unset($items[$item_id]);
        }
      }
    }
  }

}
