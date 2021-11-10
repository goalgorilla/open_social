<?php

namespace Drupal\social_demo\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DemoContent annotation object.
 *
 * @Annotation
 */
class DemoContent extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The content type label.
   *
   * @var string
   */
  public string $label;

  /**
   * The source file.
   *
   * @var string
   */
  public string $source;

  /**
   * The entity type id.
   *
   * @var string
   */
  public string $entityType;

}
