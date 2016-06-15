<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Update\UpdateBase.
 */

namespace Drupal\bootstrap\Plugin\Update;

use Drupal\bootstrap\Plugin\PluginBase;
use Drupal\bootstrap\Theme;

/**
 * Base class for an update.
 */
class UpdateBase extends PluginBase implements UpdateInterface {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return isset($this->pluginDefinition['description']) ? $this->pluginDefinition['description'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLevel() {
    return isset($this->pluginDefinition['level']) ? $this->pluginDefinition['level'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return !empty($this->pluginDefinition['title']) ? $this->pluginDefinition['title'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function update(Theme $theme) {}

}
