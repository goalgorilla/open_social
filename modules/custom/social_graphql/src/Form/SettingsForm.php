<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings for GraphQL API authentication.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_graphql_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['social_graphql.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['allow_session_auth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow session (cookie) authentication for the GraphQL API'),
      '#description' => $this->t('Needed only for integrations calling /graphql with a logged-in browser session, including the GraphQL Explorer. Has no effect while Real-Time Chat is installed.'),
      '#default_value' => (bool) $this->config('social_graphql.settings')->get('allow_session_auth'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('social_graphql.settings')
      ->set('allow_session_auth', (bool) $form_state->getValue('allow_session_auth'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
