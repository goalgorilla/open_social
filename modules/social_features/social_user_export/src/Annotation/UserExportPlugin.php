<?php

namespace Drupal\social_user_export\Annotation;

use Drupal\Core\Annotation\Translation;
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
  public string $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public int $weight;

}
