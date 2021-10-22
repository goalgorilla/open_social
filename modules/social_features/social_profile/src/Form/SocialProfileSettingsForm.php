<?php

namespace Drupal\social_profile\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_profile\FieldManager;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure behaviour of profiles in Open Social.
 *
 * This form modifies more than just module settings and uses a combination of
 * field configuration and permissions to control access to viewing and editing
 * of profile fields.
 *
 * Permissions are created per bundle to allow the same flexibility across all
 * bundle types (e.g. company or organizational profiles). However this module
 * contains only the default 'profile' profile type which is what Open Social
 * supports out of the box.
 *
 * @todo Support individual address sub-fields.
 *
 * @see \Drupal\social_profile\ProfileFieldsPermissionProvider
 * @see \social_profile_entity_field_access()
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
   * Our Social Profile Field Manager.
   *
   * Contains methods to help us know whether we're managing particular fields.
   */
  private FieldManager $fieldManager;

  /**
   * Fields synced from User to Profile.
   *
   * The storage for some fields is on the user entity rather than the
   * profile field, but the value is synced for display. So the user entity
   * fields should be added to the registration field.
   *
   * @var string[]
   */
  protected static array $syncedProfileFields = [
    "field_profile_email" => "mail",
    "field_profile_preferred_language" => "preferred_langcode",
  ];

  /**
   * SocialProfileSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\social_profile\FieldManager $field_manager
   *   The Social Profile Field Manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $database, LanguageManager $language_manager, FieldManager $field_manager) {
    parent::__construct($config_factory);
    $this->database = $database;
    $this->languageMananger = $language_manager;
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('language_manager'),
      $container->get('social_profile.field_manager')
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
    // @todo migrate social_profile_show_* settings to permissions.
    $config = $this->config('social_profile.settings');

    // @todo When the verified user role is created allow configuration of `"view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile fields"` permission to determine what role(s) count as community.
    $form['fields'] = $this->buildFieldsFieldset();

    // @todo Move to separate function.
    // @todo Rename to "Nickname".
    $form['privacy'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Privacy'),
      '#open' => TRUE,
    ];

    // Add setting to hide Full Name for users without the `social profile
    // privacy always show full name` permission.
    $form['privacy']['limit_search_and_mention'] = [
      '#type' => 'checkbox',
      '#title' => t('Limit search and mention'),
      '#description' => t("Enabling this setting causes users' full name to be hidden on the platform when the user has filled in their nickname. This setting won't hide the full name of users who didn't fill in a nickname. Users with the '%display_name' permission will still see the full name whenever available. Only users with the '%search_name' permission will find users using their full name through search or mentions.", [
        '%display_name' => t('View full name when restricted'),
        '%search_name' => t('View full name when restricted'),
      ]),
      '#default_value' => $config->get('limit_search_and_mention'),
    ];

    $form['nickname_unique_validation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique nicknames'),
      '#description' => $this->t('If you check this, validation is applied that verifies the users nickname is unique whenever they save their profile.'),
      '#default_value' => $config->get('nickname_unique_validation'),
    ];

    // Profile tagging settings.
    $form['tagging'] = $this->buildTaggingFieldset();

    return parent::buildForm($form, $form_state);
  }

  /**
   * The fieldset to control profile field settings.
   *
   * @return array
   *   The form fields for the profile fields configuration.
   */
  private function buildFieldsFieldset() : array {
    $fields = [
      '#type' => 'fieldset',
      '#title' => new TranslatableMarkup('Profile Fields'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    // We use a separate description here because the Gin theme only renders the
    // description for the fieldset at the bottom which is not discoverable for
    // users.
    $fields['description'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="form-item__description">{{ description }}</div>',
      '#context' => [
        'description' => new TranslatableMarkup("This form allows you to control the availability and behaviour of profile fields in your community. <em class='placeholder'>Public</em> visibility make fields visible to anonymous users. <em class='placeholder'>Community</em> visibility fields are only visible to logged in users. <em class='placeholder'>Private</em> fields are visible only to the user themselves. When users are allowed to edit the visibility the field will be made available on their account settings page. Roles selected under <em class='placeholder'>Always show for</em> will be able to view fields from a user's profile regardless of the visibility. Disabling a field will remove it from all places on the platform, data that users have filled in previously will not be lost."),
      ],
    ];

    $fields['list'] = [
      '#type' => 'table',
      '#sticky' => TRUE,
      '#header' => [
        new TranslatableMarkup('Field name'),
        new TranslatableMarkup('Visibility'),
        new TranslatableMarkup('Always show for'),
        new TranslatableMarkup('Allow editing'),
        new TranslatableMarkup('At registration'),
        new TranslatableMarkup('Required'),
        new TranslatableMarkup('Disabled'),
      ],
    ];

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface|NULL $registration_user_form_display */
    $registration_user_form_display = EntityFormDisplay::load("user.user.register");
    // This should be created by the install or update hook. If this does not
    // exist we have a configuration bug.
    if ($registration_user_form_display === NULL) {
      throw new \RuntimeException("Form display mode user.user.register does not exist.");
    }

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface|NULL $registration_profile_form_display */
    $registration_profile_form_display = EntityFormDisplay::load("profile.profile.register");
    // This should be created by the install or update hook. If this does not
    // exist we have a configuration bug.
    if ($registration_profile_form_display === NULL) {
      throw new \RuntimeException("Form display mode profile.profile.register does not exist.");
    }

    /** @var \Drupal\user\RoleInterface[] $roles */
    $roles = Role::loadMultiple();

    // The authenticated and site manager role have some special behaviour in
    // Open Social so if they're not set we have a broken installation that we
    // can't configure.
    if (!isset($roles['authenticated'], $roles['sitemanager'])) {
      return [];
    }

    // The roles that can be selected to "always view" or "always edit".
    $override_roles = array_filter(
      $roles,
      fn (RoleInterface $role) => !in_array(
        $role->id(),
        ['anonymous', 'authenticated', 'administrator']
      ),
    );

    /** @var string $field_name */
    foreach ($this->fieldManager->getManagedProfileFieldDefinitions() as $field_name => $field_config) {
      $visibility_field_name = $this->fieldManager::getVisibilityFieldName($field_config);

      // A field shouldn't show up in getManagedProfileFieldDefinitions if
      // getVisibilityFieldName returns NULL.
      if ($visibility_field_name === NULL) {
        $this->logger('social_profile')->critical("Field '${field_name}' is marked as managed but does not have a visibility field name. This is an implementation error.");
        continue;
      }

      /** @var \Drupal\field\FieldConfigInterface|NULL $visibility_field */
      $visibility_field = FieldConfig::loadByName("profile", "profile", $visibility_field_name);
      if ($visibility_field === NULL) {
        $this->logger('social_profile')->warning("Visibility field '${visibility_field_name}' is configured for '${field_name}' but the visibility field does not exist on the profile profile.");
        continue;
      }
      // Disable configuration fields if the field is disabled.
      $disabled_states = [
        'disabled' => [
          ':input[name="fields[list][' . $field_name . '][disabled]"]' => ['checked' => TRUE],
        ],
      ];

      // Build the row as individual parts to allow for some more creative
      // processing that can't be done in a single array declaration.
      $row = [];

      // Field name.
      $label = $field_config->label();
      if ($this->currentUser()->hasPermission("view debug info")) {
        $label .= " (${field_name}, ${visibility_field_name})";
      }
      $row['label'] = ['#plain_text' => $label];

      // Visibility.
      $row['visibility'] = [
        // Container needed until
        // https://www.drupal.org/project/drupal/issues/2945727 is fixed
        // because the #states API uses closest on a class that isn't added
        // for radios and checkboxes.
        '#type' => 'container',
        'user' => [
          '#type' => 'checkbox',
          '#title' => new TranslatableMarkup('User can edit'),
          '#default_value' => $roles['authenticated']->hasPermission("edit own ${visibility_field_name} profile profile field"),
          '#states' => $disabled_states,
        ],
        'default' => [
          '#type' => 'radios',
          '#options' => [
            SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC => new TranslatableMarkup('Public'),
            SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY => new TranslatableMarkup('Community'),
            SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE => new TranslatableMarkup('Private'),
          ],
          '#default_value' => $visibility_field->getDefaultValueLiteral()[0]['value'] ?? SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE,
          '#states' => $disabled_states,
        ],
      ];

      // Always show for.
      $row['always_show'] = [
        // Manually create a checkboxes-like element so that we can disable an
        // individual checkbox to disallow editing the site manager setting.
        '#type' => 'container',
        '#attributes' => ['class' => ['form-checkboxes']],
      ];

      foreach ($override_roles as $role_id => $role) {
        $row['always_show'][$role_id] = [
          '#type' => 'checkbox',
          '#title' => $role->label(),
          '#default_value' => $role->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${field_name} profile profile fields"),
          '#disabled' => $role_id === 'sitemanager',
          '#states' => $role_id === 'sitemanager' ? [] : $disabled_states,
        ];
      }

      // Allow editing.
      $row['allow_editing'] = [
        // Container needed until
        // https://www.drupal.org/project/drupal/issues/2945727 is fixed
        // because the #states API uses closest on a class that isn't added
        // for radios and checkboxes.
        '#type' => 'container',
        'user' => [
          '#type' => 'checkbox',
          '#title' => new TranslatableMarkup('User can edit'),
          // Users can always change their preferred language.
          // @todo Move this into a third party field setting rather than
          // special-casing names.
          '#disabled' => $field_name === 'field_profile_preferred_language',
          '#default_value' => $roles['authenticated']->hasPermission("edit own ${field_name} profile profile field"),
          '#states' => $field_name === 'field_profile_preferred_language' ? [] : $disabled_states,
        ],
        'other' => [
          // Manually create a checkboxes-like element so that we can disable an
          // individual checkbox to disallow editing the site manager setting.
          '#type' => 'container',
          '#attributes' => ['class' => ['form-checkboxes']],
        ],
      ];

      foreach ($override_roles as $role_id => $role) {
        $row['allow_editing']['other'][$role_id] = [
          '#type' => 'checkbox',
          '#title' => $role->label(),
          '#default_value' => $role->hasPermission("edit any ${field_name} profile profile field"),
          '#disabled' => $role_id === 'sitemanager',
          '#states' => $role_id === 'sitemanager' ? [] : $disabled_states,
        ];
      }

      // Show during registration.
      $stored_on_user_entity = isset(static::$syncedProfileFields[$field_name]);
      // Email is stored on the user entity but not configured in the view mode,
      // it's added by AccountForm instead.
      $registration_is_checked = $field_name === "field_profile_email" || (
        $stored_on_user_entity
          ? $registration_user_form_display->getComponent(static::$syncedProfileFields[$field_name]) !== NULL
          : $registration_profile_form_display->getComponent($field_name) !== NULL
      );
      $row['registration'] = [
        '#type' => 'checkbox',
        '#title' => new TranslatableMarkup('Show'),
        // Users are required to enter an email during registration so this
        // setting can not be changed.
        // @todo Move this into a third party field setting rather than
        // special-casing names.
        '#disabled' => $field_name === 'field_profile_email',
        '#default_value' => $registration_is_checked,
        '#states' => $field_name === 'field_profile_email' ? [] : $disabled_states,
      ];

      // Required.
      $row['required'] = [
        '#type' => 'checkbox',
        '#title' => new TranslatableMarkup('Required'),
        // Users must have an email and preferred language so these fields
        // are required.
        // @todo Move this into a third party field setting rather than
        // special-casing names.
        '#disabled' => $field_name === 'field_profile_email' || $field_name === 'field_profile_preferred_language',
        '#default_value' => $field_config->isRequired(),
        '#states' => ($field_name === 'field_profile_email' || $field_name === 'field_profile_preferred_language') ? [] : $disabled_states,
      ];

      // Disabled.
      $row['disabled'] = [
        '#type' => 'checkbox',
        '#title' => new TranslatableMarkup('Disabled'),
        // Email and preferred language are always available so these fields
        // can't be disabled but only hidden.
        // @todo Move this into a third party field setting rather than
        // special-casing names.
        '#disabled' => $field_name === 'field_profile_email' || $field_name === 'field_profile_preferred_language',
        '#default_value' => !$field_config->status(),
      ];

      $fields['list'][$field_name] = $row;
    }

    return $fields;
  }

  /**
   * The fieldset to control tagging settings.
   *
   * @return array
   *   The form fields for the tagging configuration.
   */
  private function buildTaggingFieldset() : array {
    $config = $this->config('social_profile.settings');

    $tagging = [
      '#type' => 'fieldset',
      '#title' => $this->t('Profile Tags'),
      '#open' => TRUE,
    ];

    // Get profile vocabulary overview page link.
    $profile_tags = Link::createFromRoute('profile tags', 'entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => 'profile_tag']);

    $tagging['enable_profile_tagging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow profile tagging for content managers'),
      '#required' => FALSE,
      '#default_value' => $config->get('enable_profile_tagging'),
      '#description' => $this->t('Determine whether content managers are allowed to add @profile_tags terms to the users profile.',
        [
          '@profile_tags' => $profile_tags->toString(),
        ]),
    ];

    $tagging['allow_tagging_for_lu'] = [
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

    $tagging['allow_category_split'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow category split'),
      '#default_value' => $config->get('allow_category_split'),
      '#required' => FALSE,
      '#description' => $this->t("Determine if the main categories of the vocabulary will be used as separate tag fields or as a single tag field when using tags on profile."),
    ];

    $tagging['use_category_parent'] = [
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

    return $tagging;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('social_profile.settings')
      ->set('limit_search_and_mention', $form_state->getValue('limit_search_and_mention'))
      ->set('enable_profile_tagging', $form_state->getValue('enable_profile_tagging'))
      ->set('allow_tagging_for_lu', $form_state->getValue('allow_tagging_for_lu'))
      ->set('allow_category_split', $form_state->getValue('allow_category_split'))
      ->set('use_category_parent', $form_state->getValue('use_category_parent'))
      ->set('nickname_unique_validation', $form_state->getValue('nickname_unique_validation'))
      ->save();

    // Keep track of changed roles so we can only save once.
    $modified_roles = [];

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface|NULL $registration_user_form_display */
    $registration_user_form_display = EntityFormDisplay::load("user.user.register");
    // This should be created by the install or update hook. If this does not
    // exist we have a configuration bug.
    if ($registration_user_form_display === NULL) {
      throw new \RuntimeException("Form display mode user.user.register does not exist.");
    }

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface|NULL $registration_profile_form_display */
    $registration_profile_form_display = EntityFormDisplay::load("profile.profile.register");
    // This should be created by the install or update hook. If this does not
    // exist we have a configuration bug.
    if ($registration_profile_form_display === NULL) {
      throw new \RuntimeException("Form display mode profile.profile.register does not exist.");
    }

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $default_profile_form_display */
    $default_profile_form_display = EntityFormDisplay::load("profile.profile.default");

    /** @var \Drupal\user\RoleInterface[] $roles */
    $roles = Role::loadMultiple();

    // See https://www.drupal.org/project/drupal/issues/2818877.
    /**
     * @var string $field_name
     * @var \Drupal\Core\Field\FieldConfigInterface&\Drupal\field\FieldConfigInterface $field_config
     */
    foreach ($this->fieldManager->getManagedProfileFieldDefinitions() as $field_name => $field_config) {
      $visibility_field_name = $this->fieldManager::getVisibilityFieldName($field_config);

      // A field shouldn't show up in getManagedProfileFieldDefinitions if
      // getVisibilityFieldName returns NULL.
      if ($visibility_field_name === NULL) {
        $this->logger('social_profile')->critical("Field '${field_name}' is marked as managed but does not have a visibility field name. This is an implementation error.");
        continue;
      }

      /** @var \Drupal\field\FieldConfigInterface|NULL $visibility_field */
      $visibility_field = FieldConfig::loadByName("profile", "profile", $visibility_field_name);
      if ($visibility_field === NULL) {
        $this->logger('social_profile')->warning("Visibility field '${visibility_field_name}' is configured for '${field_name}' but the visibility field does not exist on the profile profile.");
        continue;
      }

      // Visibility.
      $default_visibility = $form_state->getValue(
        ["fields", "list", $field_name, "visibility", "default"]
      );
      $visibility_field_config = $visibility_field->getConfig('profile');
      $visibility_field_config->setDefaultValue($default_visibility);
      $visibility_field_config->save();

      $user_can_edit_visibility = (bool) $form_state->getValue(
        ["fields", "list", $field_name, "visibility", "user"]
      );
      if ($user_can_edit_visibility) {
        $roles['authenticated']->grantPermission("edit own ${visibility_field_name} profile profile field");
      }
      else {
        $roles['authenticated']->revokePermission("edit own ${visibility_field_name} profile profile field");
      }

      // Always show for.
      $always_show_roles = $form_state->getValue(
        ["fields", "list", $field_name, "always_show"]
      );
      foreach ($always_show_roles as $role_id => $value) {
        if (!isset($roles[$role_id])) {
          continue;
        }

        // The site manager is always granted the permission even if the value
        // is empty (which happens when a field is disabled).
        if ($role_id === "sitemanager" || (bool) $value) {
          $roles[$role_id]->grantPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${field_name} profile profile fields");
        }
        else {
          $roles[$role_id]->revokePermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${field_name} profile profile fields");
        }

        $modified_roles[$role_id] = $roles[$role_id];
      }

      // Allow editing.
      $user_can_edit_value = (bool) $form_state->getValue(
        ["fields", "list", $field_name, "allow_editing", "user"]
      );
      if ($user_can_edit_value) {
        $roles['authenticated']->grantPermission("edit own ${field_name} profile profile field");
      }
      else {
        $roles['authenticated']->revokePermission("edit own ${field_name} profile profile field");
      }

      $modified_roles['authenticated'] = $roles['authenticated'];

      $allow_editing_roles = $form_state->getValue(
        ["fields", "list", $field_name, "allow_editing", "other"]
      );
      foreach ($allow_editing_roles as $role_id => $value) {
        if (!isset($roles[$role_id])) {
          continue;
        }

        // The site manager is always granted the permission even if the value
        // is empty (which happens when a field is disabled).
        if ($role_id === "sitemanager" || (bool) $value) {
          $roles[$role_id]->grantPermission("edit any ${field_name} profile profile field");
        }
        else {
          $roles[$role_id]->revokePermission("edit any ${field_name} profile profile field");
        }

        $modified_roles[$role_id] = $roles[$role_id];
      }

      // Show during registration.
      $field_on_registration = (bool) $form_state->getValue(
        ["fields", "list", $field_name, "registration"]
      );
      $stored_on_user_entity = isset(static::$syncedProfileFields[$field_name]);
      if (!$stored_on_user_entity) {
        if (!$field_on_registration && $registration_profile_form_display->getComponent($field_name) !== NULL) {
          $registration_profile_form_display->removeComponent($field_name);
        }
        elseif ($field_on_registration && $registration_profile_form_display->getComponent($field_name) === NULL) {
          // Use the same settings as on the default form. This ensures a
          // consistent order (weight) and consistent choice of widget.
          $default_form_component = $default_profile_form_display->getComponent($field_name);

          $registration_profile_form_display->setComponent(
            $field_name,
            $default_form_component ?? []
          );
        }
      }
      // Email is always added in AccountForm so we can't configure it.
      elseif ($field_name !== "field_profile_email") {
        $translated_field_name = static::$syncedProfileFields[$field_name];
        if (!$field_on_registration && $registration_profile_form_display->getComponent($translated_field_name) !== NULL) {
          $registration_user_form_display->removeComponent($translated_field_name);
        }
        elseif ($field_on_registration && $registration_profile_form_display->getComponent($translated_field_name) === NULL) {
          $registration_user_form_display->setComponent($translated_field_name);
        }
      }

      // Required.
      $field_config->setRequired(
        (bool) $form_state->getValue(["fields", "list", $field_name, "required"])
      );

      // Disabled.
      $disabled = (bool) $form_state->getValue(
        ["fields", "list", $field_name, "disabled"]
      );
      $field_config->setStatus(!$disabled);

      $field_config->save();
    }

    $registration_profile_form_display->save();

    foreach ($modified_roles as $role) {
      $role->save();
    }

    parent::submitForm($form, $form_state);
  }

}
