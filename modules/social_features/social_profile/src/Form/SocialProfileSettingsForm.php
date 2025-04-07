<?php

namespace Drupal\social_profile\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_profile\GroupAffiliation;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * SocialProfileSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $database, LanguageManager $language_manager, AccountInterface $current_user) {
    parent::__construct($config_factory);
    $this->database = $database;
    $this->languageMananger = $language_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('language_manager'),
      $container->get('current_user'),
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

    $form['tagging'] = [
      '#type' => 'details',
      '#title' => $this->t('Tag settings'),
      '#open' => TRUE,
    ];

    // Get profile vocabulary overview page link.
    $profile_tags = Link::createFromRoute('profile tags', 'entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => 'profile_tag']);

    $form['tagging']['enable_profile_tagging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow profile tagging for content managers'),
      '#required' => FALSE,
      '#default_value' => $config->get('enable_profile_tagging'),
      '#description' => $this->t('Determine whether content managers are allowed to add @profile_tags terms to the users profile.',
        [
          '@profile_tags' => $profile_tags->toString(),
        ]),
    ];

    $form['tagging']['allow_tagging_for_lu'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow profile tagging for regular users'),
      '#default_value' => $config->get('allow_tagging_for_lu'),
      '#required' => FALSE,
      '#description' => $this->t("Determine whether regular users are allowed to add profile tags to their own profile."),
      '#states' => [
        'visible' => [
          ':input[name="enable_profile_tagging"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['tagging']['allow_category_split'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow category split'),
      '#default_value' => $config->get('allow_category_split'),
      '#required' => FALSE,
      '#description' => $this->t("Determine if the main categories of the vocabulary will be used as separate tag fields or as a single tag field when using tags on profile."),
    ];

    $form['tagging']['use_category_parent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow parents to be used as tag'),
      '#default_value' => $config->get('use_category_parent'),
      '#required' => FALSE,
      '#description' => $this->t("Determine if the parent of categories will be used with children tags."),
      '#states' => [
        'visible' => [
          ':input[name="allow_category_split"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // @todo Until affiliation feature is not completed, this settings should
    //   be available only for administrator role.
    if (in_array('administrator', $this->currentUser->getRoles())) {
      $form['group_affiliation'] = [
        '#type' => 'details',
        '#title' => $this->t('Affiliation settings'),
        '#open' => TRUE,
      ];

      $form['group_affiliation']['group_affiliation_status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable affiliations'),
        '#default_value' => $config->get('group_affiliation_status') ?? FALSE,
        '#required' => FALSE,
        '#description' => $this->t("Allow members to determine which organization they represent in your community. This can be made through the profile settings."),
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
    $config->set('social_profile_show_email', $form_state->getValue('social_profile_show_email'));
    $config->set('enable_profile_tagging', $form_state->getValue('enable_profile_tagging'));
    $config->set('allow_category_split', $form_state->getValue('allow_category_split'));
    $config->set('use_category_parent', $form_state->getValue('use_category_parent'));
    $config->set('allow_tagging_for_lu', $form_state->getValue('allow_tagging_for_lu'));

    if (in_array('administrator', $this->currentUser->getRoles())) {
      $config->set('group_affiliation_status', $form_state->getValue('group_affiliation_status'));
    }

    $config->save();

    // Check if the website is multilingual.
    if ($this->languageMananger->isMultilingual()) {
      $config->set('social_profile_show_language', $form_state->getValue('social_profile_show_language'))
        ->save();
    }

    // Invalidate profile cache tags.
    $query = $this->database->select('profile', 'p');
    $query->addField('p', 'profile_id');
    $query->condition('p.type', 'profile');
    $query->condition('p.status', '1');
    $ids = $query->execute()->fetchCol();

    if (!empty($ids)) {
      $cache_tags = [];
      foreach ($ids as $id) {
        $cache_tags[] = 'profile:' . $id;
      }
      Cache::invalidateTags($cache_tags);
    }

    // Invalidate affiliation cache tag.
    Cache::invalidateTags([
      GroupAffiliation::GENERAL_CACHE_TAG,
    ]);

    parent::submitForm($form, $form_state);
  }

}
