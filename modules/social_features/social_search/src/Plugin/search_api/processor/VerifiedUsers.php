<?php

namespace Drupal\social_search\Plugin\search_api\processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_user\VerifyableUserInterface;

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
  public function alterIndexedItems(array &$items): void {
    foreach ($items as $item_id => $item) {
      if ($item->getOriginalObject() === NULL) {
        return;
      }

      $object = $item->getOriginalObject()->getValue();

      if ($object instanceof ProfileInterface) {
        $owner = $object->getOwner();
        // Profile owner ID is the user ID.
        if ($owner instanceof VerifyableUserInterface && !$owner->isVerified()) {
          unset($items[$item_id]);
        }
      }
      elseif ($object instanceof VerifyableUserInterface && !$object->isVerified()) {
        unset($items[$item_id]);
      }
    }
  }

}
