<?php

namespace Drupal\social_comment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CommentSettingsForm.
 *
 * @package Drupal\social_comment\Form
 */
class CommentSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_comment.comment_settings',
    ];
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
    $config = $this->config('social_comment.comment_settings');

    $form['redirect_comment_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect comment to entity'),
      '#default_value' => $config->get('redirect_comment_to_entity'),
    ];

    $form['remove_author_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove author field from the comment forms'),
      '#default_value' => $config->get('remove_author_field'),
    ];

    $form['wysiwyg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Authenticated users are allowed to use WYSIWYG editor in comment form'),
      '#default_value' => $config->get('wysiwyg'),
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('social_comment.comment_settings')
      ->set('redirect_comment_to_entity', $form_state->getValue('redirect_comment_to_entity'))
      ->set('remove_author_field', $form_state->getValue('remove_author_field'))
      ->set('wysiwyg', $form_state->getValue('wysiwyg'))
      ->save();

    if ($form_state->getValue('wysiwyg') == TRUE) {
      user_role_grant_permissions('authenticated', ['use text format comment_text']);
    }
  }

}
