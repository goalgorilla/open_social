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
   * @return array
   *   A render array for the action link, empty if the user does not have
   *   access.
   */
  public function build($entity_type_id, $entity_id);

}
