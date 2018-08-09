<?php

namespace Drupal\social_user_export\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a User export plugin item annotation object.
 *
 * @see \Drupal\social_user_export\Plugin\UserExportPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class UserExportPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

}
