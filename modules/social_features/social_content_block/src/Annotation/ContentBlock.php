<?php

namespace Drupal\social_content_block\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Content Block annotation object.
 *
 * @ingroup content_block_api
 *
 * @Annotation
 */
class ContentBlock extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The entity type ID.
   *
   * @var string
   */
  public $entityTypeId;

  /**
   * The bundle.
   *
   * @var string
   */
  public $bundle = NULL;

  /**
   * An array of fields.
   *
   * @var array
   */
  public $fields;

}
