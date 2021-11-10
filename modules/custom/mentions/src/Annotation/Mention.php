<?php

namespace Drupal\mentions\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines Mention Type annotation object.
 *
 * @Annotation
 */
class Mention extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The name of the flavor.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $name;

}
