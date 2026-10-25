<?php

declare(strict_types=1);

namespace Drupal\social_sharing\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allows site managers to manage sharing of content to other platforms.
 */
class SocialSharingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'social_sharing.settings',
      'shariff.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_sharing_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable sharing'),
      '#description' => $this->t('Whether members can share public content to other platforms. When disabled, the "Share this page" block and the sharing actions shown when selecting text are removed from the platform.'),
      '#config_target' => 'social_sharing.settings:enabled',
    ];

    $enabled_services = $this->config('shariff.settings')->get('shariff_services') ?? [];
    $form['services'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Sharing services'),
      '#description' => $this->t('The services members can share content to.'),
      '#options' => $this->getServiceOptions($enabled_services),
      '#default_value' => array_values($enabled_services),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('enabled') && !array_filter($form_state->getValue('services'))) {
      $form_state->setErrorByName('services', $this->t('Select at least one sharing service or disable sharing.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // The services are only shown and validated when sharing is enabled, so
    // keep the previous selection when sharing gets disabled.
    if ($form_state->getValue('enabled')) {
      $services = array_filter($form_state->getValue('services'));
      // Keep the order of the options, which is the order of the buttons.
      $services = array_intersect_key($form['services']['#options'], $services);
      $services = array_combine(array_keys($services), array_keys($services));

      $this->config('shariff.settings')
        ->set('shariff_services', $services)
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns the Shariff services with the enabled ones first, in their order.
   *
   * @param array<string, string> $enabled_services
   *   The enabled services, keyed by service.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   The service labels, keyed by service.
   */
  private function getServiceOptions(array $enabled_services): array {
    // Shariff does not expose its services, so mirror the list from
    // \Drupal\shariff\Form\ShariffSettingsForm::getServices().
    $services = [
      'twitter' => $this->t('Twitter'),
      'facebook' => $this->t('Facebook'),
      'linkedin' => $this->t('LinkedIn'),
      'pinterest' => $this->t('Pinterest'),
      'vk' => $this->t('VK'),
      'xing' => $this->t('Xing'),
      'whatsapp' => $this->t('WhatsApp'),
      'addthis' => $this->t('AddThis'),
      'telegram' => $this->t('Telegram'),
      'tumblr' => $this->t('Tumblr'),
      'flattr' => $this->t('Flattr'),
      'diaspora' => $this->t('Diaspora'),
      'reddit' => $this->t('reddit'),
      'stumbleupon' => $this->t('StumbleUpon'),
      'weibo' => $this->t('Weibo'),
      'flipboard' => $this->t('Flipboard'),
      'pocket' => $this->t('Pocket'),
      'print' => $this->t('Print'),
      'tencent-weibo' => $this->t('Tencent-Weibo'),
      'qzone' => $this->t('Qzone'),
      'threema' => $this->t('Threema'),
      'mail' => $this->t('E-Mail'),
      'info' => $this->t('Info Button'),
      'buffer' => $this->t('Buffer'),
    ];

    $ordered_services = [];
    foreach (array_keys($enabled_services) as $service) {
      if (isset($services[$service])) {
        $ordered_services[$service] = $services[$service];
      }
    }

    return $ordered_services + $services;
  }

}
