<?php

namespace Drupal\social_profile\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure social profile settings.
 */
class SocialProfileSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageMananger;

  /**
   * SocialProfileSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $database, LanguageManager $language_manager) {
    parent::__construct($config_factory);
    $this->database = $database;
    $this->languageMananger = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_profile_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_profile.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_profile.settings');

    $form['privacy'] = [
      '#type' => 'details',
      '#title' => $this->t('Privacy settings'),
      '#open' => TRUE,
    ];
    $form['privacy']['social_profile_show_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show email on all user profiles'),
      '#default_value' => $config->get('social_profile_show_email'),
      '#description' => $this->t('When enabled, users are not able to hide their email address on their profile. When disabled, users will be able to control the visibility of their emailaddress.'),
    ];
    // Check if the website is multilingual.
    if ($this->languageMananger->isMultilingual()) {
      $form['privacy']['social_profile_show_language'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show language on all user profiles'),
        '#default_value' => $config->get('social_profile_show_language'),
        '#description' => $this->t('When enabled, users are not able to hide their preferred language on their profile. When disabled, users will be able to control the visibility of their language preference.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save config.
    $config = $this->config('social_profile.settings');
    $config->set('social_profile_show_email', $form_state->getValue('social_profile_show_email'))
      ->save();

    // Check if the website is multilingual.
    if ($this->languageMananger->isMultilingual()) {
      $config->set('social_profile_show_language', $form_state->getValue('social_profile_show_language'))
        ->save();
    }

    // Invalidate profile cache tags.
    $query = $this->database->select('profile', 'p');
    $query->addField('p', 'profile_id');
    $query->condition('p.type', 'profile');
    $query->condition('p.status', 1);
    $ids = $query->execute()->fetchCol();

    if (!empty($ids)) {
      $cache_tags = [];
      foreach ($ids as $id) {
        $cache_tags[] = 'profile:' . $id;
      }
      Cache::invalidateTags($cache_tags);
    }

    parent::submitForm($form, $form_state);
  }

}
