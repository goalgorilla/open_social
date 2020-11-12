<?php

namespace Drupal\social_album\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SocialAlbumSettingsForm.
 *
 * @package Drupal\social_album\Form
 */
class SocialAlbumSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_album.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_album_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $this->config('social_album.settings')->get('status'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('social_album.settings')
      ->set('status', $form_state->getValue('status'))
      ->save();
  }

}
