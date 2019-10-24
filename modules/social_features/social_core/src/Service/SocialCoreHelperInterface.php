<?php

namespace Drupal\social_core\Service;

/**
 * Interface SocialCoreHelperInterface.
 *
 * @package Drupal\social_core\Service
 */
interface SocialCoreHelperInterface {

  /**
   * Applies all the detected valid changes.
   *
   * Use this with care, as it will apply updates for any module, which will
   * lead to unpredictable results.
   *
   * @param string $entity_type_id
   *   (optional) Applies changes only for the specified entity type ID.
   *   Defaults to NULL.
   *
   * @see \Drupal\Tests\system\Functional\Entity\Traits::applyEntityUpdates()
   */
  public function applyEntityUpdates($entity_type_id = NULL);

}
