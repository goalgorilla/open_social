<?php

namespace Drupal\social_content_block\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Multiple Content Block annotation object.
 *
 * @ingroup multiple_content_block_api
 *
 * @Annotation
 */
class MultipleContentBlock extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The entity type.
   *
   * @var string
   */
  public $entity_type;

  /**
   * The bundle.
   *
   * @var string|null
   */
  public $bundle = NULL;

}
