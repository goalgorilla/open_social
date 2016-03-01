<?php

/**
 * @file
 * Contains \Drupal\address\Annotation\ZoneMember.
 */

namespace Drupal\address\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a zone member annotation object.
 *
 * Plugin Namespace: Plugin\ZoneMember
 *
 * @Annotation
 */
class ZoneMember extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $name;

}

