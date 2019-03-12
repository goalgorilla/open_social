<?php

namespace Drupal\social_profile_fields\Form;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\ProfileType;
use Drupal\social_profile_fields\SocialProfileFieldsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure social profile settings.
 */
class SocialProfileFieldsSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * Profile fields helper.
   *
   * @var \Drupal\social_profile_fields\SocialProfileFieldsHelper
   */
  protected $profileFieldsHelper;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected $cacheTagsInvalidator;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * SocialProfileSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\social_profile_fields\SocialProfileFieldsHelper $profile_fields_helper
   *   Profile fields helper.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection for invalidating caches.
   * @param \Drupal\Core\Cache\CacheTagsInvalidator $cache_tags_invalidator
   *   Cache tags invalidator for clearing tags.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module handler for checking if modules exist.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SocialProfileFieldsHelper $profile_fields_helper, Connection $database, CacheTagsInvalidator $cache_tags_invalidator, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->profileFieldsHelper = $profile_fields_helper;
    $this->database = $database;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('social_profile_fields.helper'),
      $container->get('database'),
      $container->get('cache_tags.invalidator'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_profile_fields_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_profile_fields.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_profile_fields.settings');

    // Add an introduction text to explain what can be done here.
    $form['introduction']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Per profile type you can choose which fields you want to use. Users will not be able to edit or view fields that are deselected.'),
    ];
    $form['introduction']['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Be aware that data is <em>not removed</em>, and can possibly still be found through the search, until you flush the data.'),
    ];

    /** @var \Drupal\profile\Entity\ProfileType $profile_type */
    foreach (ProfileType::loadMultiple() as $profile_type) {
      $type = $profile_type->id();

      $form[$type] = [
        '#type' => 'details',
        '#title' => $this->t('Settings for profiletype: @profile_type', ['@profile_type' => $type]),
        '#description' => $this->t('Select the fields you want to use on this profile type.'),
        '#open' => TRUE,
      ];

      /** @var \Drupal\field\Entity\FieldConfig $field_config */
      foreach ($this->profileFieldsHelper->getProfileFields($type) as $field) {
        // Loop through the fields.
        $id = $field['id'];

        // Hiding this field on the Open Social profile will make no difference,
        // let's skip it for now.
        if ($type === 'profile' && $id === 'profile_profile_field_profile_show_email') {
          continue;
        }

        // No setting is TRUE.
        $default_value = (is_null($config->get($id)) ? TRUE : $config->get($id));

        $form[$type][$id] = [
          '#type' => 'checkbox',
          '#title' => $field['label'],
          '#description' => $field['name'],
          '#default_value' => $default_value,
        ];
      }
    }

    $form['limit_search_and_mention'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit search and mention'),
      '#description' => $this->t('When this setting is enabled then only users with the %name permission will be allowed to find members in search or mentions when using their full name.', [
        '%name' => $this->t('Always show Full Name'),
      ]),
      '#default_value' => $config->get('limit_search_and_mention'),
    ];

    $form['actions']['social_profile_fields_confirm_flush'] = [
      '#type' => 'submit',
      '#submit' => ['::submitFlush'],
      '#value' => $this->t('Flush profile data'),
      '#weight' => 5,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save config.
    $config = $this->config('social_profile_fields.settings');

    /** @var \Drupal\profile\Entity\ProfileType $profile_type */
    foreach (ProfileType::loadMultiple() as $profile_type) {
      $type = $profile_type->id();

      /** @var \Drupal\field\Entity\FieldConfig $field_config */
      foreach ($this->profileFieldsHelper->getProfileFields($type) as $field) {
        $config->set($field['id'], $form_state->getValue($field['id']));
      }
    }

    $config
      ->set('limit_search_and_mention', $form_state->getValue('limit_search_and_mention'))
      ->save();

    parent::submitForm($form, $form_state);

    // Invalidate profile cache tags.
    $query = $this->database->select('profile', 'p');
    $query->addField('p', 'profile_id');
    $query->condition('p.type', 'profile');
    $query->condition('p.status', 1);
    $ids = $query->execute()->fetchCol();

    $cache_tags = ['profile', 'profile_list', 'profile_view'];
    if (!empty($ids)) {
      foreach ($ids as $id) {
        $cache_tags[] = 'profile:' . $id;
      }
    }

    $this->cacheTagsInvalidator->invalidateTags($cache_tags);

    // If the user export module is on, clear the cached definitions.
    if ($this->moduleHandler->moduleExists('social_user_export')) {
      $user_export_manager = \Drupal::service('plugin.manager.user_export_plugin');
      $user_export_manager->clearCachedDefinitions();
    }
  }

  /**
   * Redirects to confirmation form for the flush action.
   */
  public function submitFlush(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('social_profile_fields.flush');
  }

}
