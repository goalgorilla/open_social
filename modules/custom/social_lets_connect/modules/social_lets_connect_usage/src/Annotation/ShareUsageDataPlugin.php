<?php

namespace Drupal\social_lets_connect_usage\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Share usage data plugin item annotation object.
 *
 * @see \Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ShareUsageDataPlugin extends Plugin {

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
   * The key of the setting.
   *
   * @var string
   */
  public string $setting;

}
