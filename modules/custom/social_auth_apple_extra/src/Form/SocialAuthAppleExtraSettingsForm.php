<?php

namespace Drupal\social_auth_apple_extra\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_auth_apple\Form\AppleAuthSettingsForm;

/**
 * Extends settings form for Social Auth Apple.
 *
 * @package Drupal\social_auth_apple_extra\Form
 */
class SocialAuthAppleExtraSettingsForm extends AppleAuthSettingsForm {

  /**
   * The additional routes list for extending a set of redirect URLs.
   */
  const ROUTE_NAMES = [
    'social_auth_apple_extra.user_register_callback',
    'social_auth_apple_extra.user_link_callback',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $section = &$form['apple_settings'];
    $section['#title'] = $this->t('Apple App settings');

    $section['authorized_redirect_url']['#type'] = 'textarea';
    $section['authorized_redirect_url']['#rows'] = 1;

    foreach (self::ROUTE_NAMES as $route_name) {
      $url = Url::fromRoute($route_name)->setAbsolute();
      $section['authorized_redirect_url']['#default_value'] .= ',' . $url->toString();
    }

    $section['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $this->config('social_auth_apple.settings')->get('status'),
      '#description' => $this->t('Determines whether this social network can be used.'),
    ];

    $form['social_auth']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('social_auth_apple.settings')
      ->set('status', $form_state->getValue('status'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
