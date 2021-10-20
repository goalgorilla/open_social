<?php

namespace Drupal\social_comment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to configure Album settings.
 *
 * @package Drupal\social_comment\Form
 */
class SocialCommentSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_comment.comment_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_comment_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->getEditableConfigNames()[0]);

    $form['reverse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reverse'),
      '#description' => $this->t('Display comments from newest to oldest.'),
      '#default_value' => $config->get('reverse'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config($this->getEditableConfigNames()[0])
      ->set('reverse', $form_state->getValue('reverse'))
      ->save();
  }

}
