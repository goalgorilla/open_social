<?php

namespace Drupal\social_tour\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SocialTourSettings.
 *
 * @package Drupal\social_tour\Form
 */
class SocialTourSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_tour.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_tour_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_tour.settings');

    $form['social_tour_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the social tour'),
      '#description' => $this->t('Set whether the tour is enabled or not.'),
      '#default_value' => $config->get('social_tour_enabled'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('social_tour.settings')
      ->set('social_tour_enabled', $form_state->getValue('social_tour_enabled'))
      ->save();
  }

}
