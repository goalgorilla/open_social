<?php

namespace Drupal\social_event_managers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SocialEventTypeSettings.
 *
 * @package Drupal\social_event_managers\Form
 */
class SocialEventManagersSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'social_event_managers.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_event_managers_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('social_event_managers.settings');

    $form['author_as_manager'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Author as event organiser'),
      '#description' => $this->t('Set author of event as event organiser automatically.'),
      '#default_value' => $config->get('author_as_manager'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('social_event_managers.settings')
      ->set('author_as_manager', $form_state->getValue('author_as_manager'))
      ->save();
  }

}
