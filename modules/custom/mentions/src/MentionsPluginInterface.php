<?php

namespace Drupal\mentions;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for mention plugins.
 */
interface MentionsPluginInterface extends ContainerFactoryPluginInterface {

  /**
   * The targetCallback function.
   *
   * @param string $value
   *   The value.
   * @param array $settings
   *   The settings.
   *
   * @return array
   *   Returns callback array.
   */
  public function targetCallback(string $value, array $settings): array;

  /**
   * The outputCallback function.
   *
   * @param array $mention
   *   The mention.
   * @param array $settings
   *   The settings.
   *
   * @return array
   *   Returns output array.
   */
  public function outputCallback(array $mention, array $settings): array;

}
