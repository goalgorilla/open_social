<?php

namespace Drupal\social_content_block;

/**
 * Interface ContentBuilderInterface.
 *
 * @package Drupal\social_content_block
 */
interface ContentBuilderInterface {

  /**
   * Lazy builder callback for displaying a content blocks.
   *
   * @param int $entity_id
   *   Entity ID.
   * @param string $entity_type_id
   *   Entity type id.
   * @param string $entity_bundle
   *   The bundle of the entity.
   *
   * @return array
   *   A render array for the action link, empty if the user does not have
   *   access.
   */
  public function build($entity_id, $entity_type_id, $entity_bundle) : array;

}
