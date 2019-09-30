<?php

namespace Drupal\activity_send_push_notification;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface PushInterface.
 *
 * @package Drupal\activity_send_push_notification
 */
interface PushInterface extends ContainerFactoryPluginInterface {

  /**
   * Check access.
   *
   * @return bool
   *   TRUE if it should be shown.
   */
  public function access();

  /**
   * Build form elements.
   *
   * @return array
   *   The form elements.
   */
  public function buildForm();

  /**
   * Save plugin settings.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(FormStateInterface $form_state);

}
