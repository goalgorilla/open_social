<?php

namespace Drupal\mentions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface MentionsPluginInterface.
 */
interface MentionsPluginInterface extends ContainerFactoryPluginInterface {

  /**
   * The targetCallback function.
   *
   * @param string $value
   *   The value.
   * @param array|string $settings
   *   The settings.
   *
   * @return mixed
   *   Returns mixed.
   */
  public function targetCallback($value, $settings);

  /**
   * The outputCallback function.
   *
   * @param string $mention
   *   The mention.
   * @param array|string $settings
   *   The settings.
   *
   * @return mixed
   *   Returns mixed.
   */
  public function outputCallback($mention, $settings);

  /**
   * The patternCallback function.
   *
   * @param array|string $settings
   *   The settings.
   * @param string $regex
   *   The pattern.
   *
   * @return mixed
   *   Returns mixed.
   */
  public function patternCallback($settings, $regex);

  /**
   * The settingsCallback function.
   *
   * @param \Drupal\Core\Form\FormInterface $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed $type
   *   The type.
   *
   * @return mixed
   *   Returns mixed.
   */
  public function settingsCallback(FormInterface $form, FormStateInterface $form_state, $type);

  /**
   * The settingsSubmitCallback function.
   *
   * @param \Drupal\Core\Form\FormInterface $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed $type
   *   The type.
   *
   * @return mixed
   *   Returns mixed.
   */
  public function settingsSubmitCallback(FormInterface $form, FormStateInterface $form_state, $type);

  /**
   * The mentionPresaveCallback function.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return mixed
   *   Returns mixed.
   */
  public function mentionPresaveCallback(EntityInterface $entity);

}
