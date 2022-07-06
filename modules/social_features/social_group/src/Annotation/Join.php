<?php

namespace Drupal\social_group\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Join annotation object.
 *
 * @ingroup social_group_api
 *
 * @Annotation
 */
class Join extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The entity type ID.
   */
  public ?string $entityTypeId = NULL;

  /**
   * The join method.
   */
  public ?string $method = NULL;

  /**
   * The weight.
   */
  public int $weight = 0;

}
