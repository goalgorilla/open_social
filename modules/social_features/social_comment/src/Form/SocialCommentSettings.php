<?php

namespace Drupal\social_comment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SocialTourSettings.
 *
 * @package Drupal\social_tour\Form
 */
class SocialCommentSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_comment.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_comment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_comment.settings');
    $form['social_comment_level'] = [
      '#type' => 'number',
      '#title' => $this->t('Specify how deep comments can be threaded'),
      '#description' => $this->t('Default is 1. Users can reply on a comment.<br>2 means: users can reply on a reply on a comment<br>Increasing the value to > 4 will have display issues on small devices'),
      '#default_value' => $config->get('social_comment_level') ? $config->get('social_comment_level') : 1,
      '#min' => 1,
      '#step' => 1,
      '#size' => 2,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('social_comment.settings')
      ->set('social_comment_level', $form_state->getValue('social_comment_level'))
      ->save();
  }

}
