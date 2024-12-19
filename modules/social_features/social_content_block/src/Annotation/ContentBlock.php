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
  public string $id;

  /**
   * The entity type ID.
   *
   * @var string
   */
  public string $entityTypeId;

  /**
   * The bundle.
   *
   * @var string|null
   */
  public ?string $bundle = NULL;

  /**
   * An array of fields.
   *
   * @var array
   */
  public array $fields;

}
