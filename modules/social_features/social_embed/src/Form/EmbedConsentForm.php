<?php

namespace Drupal\social_embed\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The form for different setting about embed consent.
 */
class EmbedConsentForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  private const SETTINGS = 'social_embed.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_embed_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    // Add an introduction text to explain what can be done here.
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Embedded content from third party providers, like Facebook or YouTube, require cookies to be placed in order to work.
       This settings page allows you to configure this behavior. This should help you create a better experience for your users, keeping their privacy in mind.
       When enabled, users are shown a placeholder instead of embedded content. After the user consents to show this third party content, the embedded content will be shown.'),
    ];

    $form['embed_consent_settings_lu'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce consent for all embedded content for registered users.'),
      '#default_value' => $config->get('embed_consent_settings_lu'),
      '#description' => $this->t('This setting will enforce users to give consent before showing embedded content.'),
    ];

    $form['embed_consent_settings_an'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce consent for all embedded content for anonymous users.'),
      '#description' => $this->t('This setting will enforce anonymous users to give consent before showing embedded content.'),
      '#default_value' => $config->get('embed_consent_settings_an'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) :void {
    // Retrieve the configuration.
    $config = $this->configFactory->getEditable(static::SETTINGS);
    $new_value_consent_settings_lu = $form_state->getValue('embed_consent_settings_lu');
    $new_value_consent_settings_an = $form_state->getValue('embed_consent_settings_an');
    if (($config->get('embed_consent_settings_lu') != $new_value_consent_settings_lu)
        || ($new_value_consent_settings_an != $config->get('embed_consent_settings_an'))
      ) {
      // Let's invalidate our custom tags so that render cache of such content
      // can be rebuilt and the effect of changed settings can take place.
      // @see: SocialEmbedConvertUrlToEmbedFilter
      // @see: SocialEmbedUrlEmbedFilter
      Cache::invalidateTags([
        'social_embed:filter.convert_url',
        'social_embed:filter.url_embed',
      ]);
      // Set the submitted configuration setting.
      $config->set('embed_consent_settings_lu', $new_value_consent_settings_lu)
        ->set('embed_consent_settings_an', $new_value_consent_settings_an)
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
