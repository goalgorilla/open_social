<?php

namespace Drupal\social_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SocialUserSettingsForm.
 *
 * @package Drupal\social_user\Form
 */
class SocialUserSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_user.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_user_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_user.settings');

    $form['social_user_profile_landingpage'] = [
      '#type' => 'select',
      '#title' => t('Choose a default landing page'),
      '#description' => t('When visiting a profile the user will end up at this page first'),
      '#options' => [
        'stream' => t('Stream'),
        'information' => t('Information'),
      ],
      '#default_value' => $config->get('social_user_profile_landingpage'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('social_user.settings')
      ->set('social_user_profile_landingpage', $form_state->getValue('social_user_profile_landingpage'))
      ->save();

    // Rebuild the router cache.
    \Drupal::service('router.builder')->rebuild();

    /** @var \Drupal\Core\Cache\CacheTagsInvalidator $cti */
    $cti = \Drupal::service('cache_tags.invalidator');
    $cti->invalidateTags(['rendered']);
  }

}
