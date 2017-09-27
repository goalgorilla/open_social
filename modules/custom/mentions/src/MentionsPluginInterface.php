<?php

namespace Drupal\mentions;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface MentionsPluginInterface.
 */
interface MentionsPluginInterface extends ContainerFactoryPluginInterface {

  /**
   * The targetCallback function.
   *
   * @param string $value
   *    The value.
   * @param array|string $settings
   *    The settings.
   *
   * @return mixed
   *    Returns mixed.
   */
  public function targetCallback($value, $settings);

  /**
   * The outputCallback function.
   *
   * @param string $mention
   *    The mention.
   * @param array|string $settings
   *    The settings.
   *
   * @return mixed
   *    Returns mixed.
   */
  public function outputCallback($mention, $settings);

  /**
   * The patternCallback function.
   *
   * @param array|string $settings
   *    The settings.
   * @param string $regex
   *    The pattern.
   *
   * @return mixed
   *    Returns mixed.
   */
  public function patternCallback($settings, $regex);

  public function settingsCallback($form, $form_state, $type);

  public function settingsSubmitCallback($form, $form_state, $type);

  public function mentionPresaveCallback($entity);

}
