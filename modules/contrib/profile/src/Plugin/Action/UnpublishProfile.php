<?php

/**
 * @file
 * Contains \Drupal\profile\Plugin\Action\UnpublishProfile.
 */

namespace Drupal\profile\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes a profile.
 *
 * @Action(
 *   id = "profile_unpublish_action",
 *   label = @Translation("Unpublish selected profile"),
 *   type = "profile"
 * )
 */
class UnpublishProfile extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $entity->setActive(FALSE);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
