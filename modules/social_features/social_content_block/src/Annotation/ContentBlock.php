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
   * Type of content.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $type;

  /**
   * An array of fields.
   *
   * @var array
   */
  public $fields;

}
