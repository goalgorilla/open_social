<?php

namespace Drupal\social_swiftmail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SocialSwiftmailSettingsForm.
 *
 * @package Drupal\social_swiftmail\Form
 */
class SocialSwiftmailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_swiftmail.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_swiftmail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_swiftmail.settings');

    $form['remove_open_social_branding'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove Open Social Branding'),
      '#description' => $this->t('Open Social Branding will be replaced by site name (and slogan if available).'),
      '#default_value' => $config->get('remove_open_social_branding'),
    ];

    $form['template'] = [
      '#type' => 'details',
      '#title' => $this->t('Template configuration'),
      '#open' => FALSE,
    ];
    $form['template']['template_header'] = [
      '#title' => $this->t('Template header'),
      '#type' => 'text_format',
      '#format' => 'mail_html',
      '#default_value' => $config->get('template_header') ?: '',
      '#description' => $this->t('Enter information you want to show in the email notifications header'),
    ];
    $form['template']['template_footer'] = [
      '#title' => $this->t('Template footer'),
      '#type' => 'text_format',
      '#format' => 'mail_html',
      '#default_value' => $config->get('template_footer') ?: '',
      '#description' => $this->t('Enter information you want to show in the email notifications footer'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save config.
    $config = $this->config('social_swiftmail.settings');
    $config->set('remove_open_social_branding', $form_state->getValue('remove_open_social_branding'));
    $config->set('template_header', $form_state->getValue('template_header')['value']);
    $config->set('template_footer', $form_state->getValue('template_footer')['value']);
    $config->save();
  }

}
