<?php

namespace Drupal\social_profile\Form;

use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use Drupal\address\LabelHelper;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
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
 * @todo This form currently contains a lot of logic that is ideally moved to a
 * service and make it available in API contexts. That step has been omitted
 * due to time constraints.
 *
 * @see \Drupal\social_profile\ProfileFieldsPermissionProvider
 * @see \social_profile_entity_field_access()
 */
class SocialProfileSettingsForm extends ConfigFormBase {

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
   * The entity type manager.
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Our Social Profile Field Manager.
   *
   * Contains methods to help us know whether we're managing particular fields.
   */
  private FieldManager $fieldManager;

  /**
   * Drupal's typed data manager.
   */
  private TypedDataManagerInterface $typedDataManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\social_profile\FieldManager $field_manager
   *   The Social Profile Field Manager.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed data manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, Connection $database, LanguageManager $language_manager, FieldManager $field_manager, TypedDataManagerInterface $typed_data_manager) {
    parent::__construct($config_factory);
    $this->database = $database;
    $this->languageMananger = $language_manager;
    $this->fieldManager = $field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('language_manager'),
      $container->get('social_profile.field_manager'),
      $container->get('typed_data_manager')
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
    return [
      'social_profile.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['community_visibility'] = $this->buildCommunityVisibilityFieldset();

    $form['fields'] = $this->buildFieldsFieldset();

    $form['nickname'] = $this->buildNicknameFieldset();

    $form['address'] = $this->buildAddressFieldset();

    return parent::buildForm($form, $form_state);
  }

  /**
   * The fieldset to control who can see community profile fields.
   *
   * @return array
   *   The form fields to control who can see community profile fields.
   */
  private function buildCommunityVisibilityFieldset() : array {
    $fields = [
      '#type' => 'fieldset',
      '#title' => new TranslatableMarkup('Community Visibility'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $authenticated_role = Role::load(AccountInterface::AUTHENTICATED_ROLE);
    assert($authenticated_role !== NULL, "Authenticated role is missing.");
    $account_settings = Url::fromRoute("entity.user.admin_form")->toString();

    $fields['require_verified'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup("Require users to be verified before seeing profile fields with the community visibility"),
      '#description' => new TranslatableMarkup(
        "When this is unchecked authenticated users without the “verified” role can view community profile fields. Control when users receive the “verified’ role in the <a href=':account_settings'>account settings</a>.",
        [':account_settings' => $account_settings]
      ),
      '#default_value' => !$authenticated_role->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile profile fields"),
    ];

    return $fields;
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
      '#type' => 'item',
      '#description' => new TranslatableMarkup("This form allows you to control the availability and behaviour of profile fields in your community. <em class='placeholder'>Public</em> visibility make fields visible to anonymous users. <em class='placeholder'>Community</em> visibility fields are only visible to logged in users. <em class='placeholder'>Private</em> fields are visible only to the user themselves. When users are allowed to edit the visibility the field will be made available on their account settings page. Roles selected under <em class='placeholder'>Always show for</em> will be able to view fields from a user's profile regardless of the visibility. Disabling a field will remove it from all places on the platform, data that users have filled in previously will not be lost."),
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
          '#title' => new TranslatableMarkup('User can edit <span class="visually-hidden">:field visibility</span>', [':field' => $label]),
          '#default_value' => $roles['authenticated']->hasPermission("edit own ${visibility_field_name} profile profile field"),
          '#states' => $disabled_states,
        ],
        'default' => [
          '#type' => 'radios',
          '#options' => [
            SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC => new TranslatableMarkup('Public <span class="visually-hidden">visibility for :field field</span>', [':field' => $label]),
            SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY => new TranslatableMarkup('Community <span class="visually-hidden">visibility for :field field</span>', [':field' => $label]),
            SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE => new TranslatableMarkup('Private <span class="visually-hidden">visibility for :field field</span>', [':field' => $label]),
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
          '#title' => new TranslatableMarkup('<span class="visually-hidden">Always show :field field for</span> :role', [':field' => $label, ':role' => $role->label()]),
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
          '#title' => new TranslatableMarkup('User can edit <span class="visually-hidden">:field field value</span>', [':field' => $label]),
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
          '#title' => new TranslatableMarkup('<span class="visually-hidden">Allow editing :field field by</span> :role', [':field' => $label, ':role' => $role->label()]),
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
        !$stored_on_user_entity && $registration_profile_form_display->getComponent($field_name) !== NULL
      );
      $row['registration'] = [
        '#type' => 'checkbox',
        '#title' => new TranslatableMarkup('<span class="visually-hidden">Show :field field</span> At registration', [':field' => $label]),
        // Fields that are stored on the user entity can not be removed or added
        // because that form is controlled by `AccountForm` with various access
        // checks without the Field UI.
        '#disabled' => $stored_on_user_entity,
        '#default_value' => $registration_is_checked,
        '#states' => $field_name === 'field_profile_email' ? [] : $disabled_states,
      ];

      // Required.
      $row['required'] = [
        '#type' => 'checkbox',
        '#title' => new TranslatableMarkup('<span class="visually-hidden">:field field is</span> Required', [':field' => $label]),
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
        '#title' => new TranslatableMarkup('<span class="visually-hidden">:field</span> Disabled', [':field' => $label]),
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
   * Build the fieldset for nickname related settings.
   *
   * @return array
   *   The nickname field related settings.
   */
  private function buildNicknameFieldset() : array {
    $config = $this->config('social_profile.settings');

    $fields = [
      '#type' => 'fieldset',
      '#title' => new TranslatableMarkup('Nickname'),
      '#open' => TRUE,
      '#tree' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="fields[list][field_profile_nick_name][disabled]"]' => ['checked' => FALSE],
        ],
      ],
      'nickname_unique_validation' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Unique nicknames'),
        '#description' => $this->t('If you check this, validation is applied that verifies the users nickname is unique whenever they save their profile.'),
        '#default_value' => $config->get('nickname_unique_validation'),
      ],
    ];

    return $fields;
  }

  /**
   * Ensures the sitemanager checkbox is disabled.
   *
   * We can't change individual checkboxes elements until they're built.
   *
   * @param array $element
   *   The checkboxes element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current form state.
   *
   * @return array
   *   The updated checkboxes element.
   */
  public function alwaysShowFullNameAfterBuild(array $element, FormStateInterface $formState) : array {
    assert(isset($element['sitemanager']), "Missing sitemanager role");

    // We never allow removing this from sitemanagers.
    $element['sitemanager']['#attributes']['disabled'] = "disabled";

    return $element;
  }

  /**
   * The fieldset to control address sub-field settings.
   *
   * @return array
   *   The form fields for the address sub-field settings.
   */
  private function buildAddressFieldset() : array {
    // Type annotation needed for PHPStan until Drupal 9.2.0.
    /** @var \Drupal\field\FieldConfigInterface|NULL $address_field */
    $address_field = FieldConfig::loadByName('profile', 'profile', 'field_profile_address');
    // If the field doesn't exist or a site builder has indicated it should be
    // opted out of our management then we exit early.
    if ($address_field === NULL || $this->fieldManager::isOptedOutOfFieldAccessManagement($address_field)) {
      return [];
    }

    // We only allow certain fields to be configured and the others are locked
    // as hidden. This is because the address field allows entering some
    // information (like name and organization) that we store in other profile
    // fields. addressLine2 is not allowed because it does not contain a field
    // label and allowing changing it may cause it to be shown with addressLine1
    // if we want to show addressLine2 we should link it to the addressLine1
    // setting.
    $allowlist = [
      'administrativeArea',
      'locality',
      'postalCode',
      'addressLine1',
    ];

    $fields = [
      '#type' => 'fieldset',
      '#title' => new TranslatableMarkup('Address'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="fields[list][field_profile_address][disabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $fields['field_overrides_description'] = [
      '#type' => 'item',
      '#description' => new TranslatableMarkup('By default the available address properties will depend on the chosen country. You can override this behaviour, forcing specific properties to be always hidden, optional, or required.'),
    ];

    $fields['field_overrides'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Property'),
        $this->t('Override'),
      ],
      '#element_validate' => [[AddressItem::class, 'fieldOverridesValidate']],
    ];
    $fields['fixed_field_overrides'] = [];
    $field_overrides = $this->getAddressFieldOverrides($address_field);
    foreach (LabelHelper::getGenericFieldLabels() as $field_name => $label) {
      $override = $field_overrides[$field_name] ?? '';

      if (in_array($field_name, $allowlist, TRUE)) {
        $fields['field_overrides'][$field_name] = [
          'field_label' => [
            '#type' => 'markup',
            '#markup' => $label,
          ],
          'override' => [
            '#type' => 'select',
            '#options' => [
              FieldOverride::HIDDEN => $this->t('Hidden'),
              FieldOverride::OPTIONAL => $this->t('Optional'),
              FieldOverride::REQUIRED => $this->t('Required'),
            ],
            '#default_value' => $override,
            '#empty_option' => $this->t('- No override -'),
          ],
        ];
      }
      elseif ($override !== '') {
        // Keep non-empty overrides that may not be changed around as value so
        // they don't disappear from the settings.
        $fields['fixed_field_overrides'][$field_name] = [
          'override' => [
            '#parent' => ['address', 'field_overrides'],
            '#type' => 'value',
            '#value' => $override,
          ],
        ];
      }
    }

    return $fields;
  }

  /**
   * Gets the field overrides for the address field.
   *
   * @param \Drupal\field\FieldConfigInterface $addressField
   *   The address field to get overrides for.
   *
   * @return array
   *   FieldOverride constants keyed by AddressField constants.
   *
   * @see \Drupal\address\Plugin\Field\FieldType\AddressItem::getFieldOverrides()
   */
  private function getAddressFieldOverrides(FieldConfigInterface $addressField) : array {
    // The fieldSettingsForm comes from the AddressItem so we need to create an
    // entity and get a field item.
    $entity_type_id = $addressField->getTargetEntityTypeId();
    $entity_bundle = $addressField->getTargetBundle();
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_values = [];
    if ($entity_bundle !== NULL && ($bundle_key = $entity_type->getKey('bundle')) && is_string($bundle_key)) {
      $entity_values[$bundle_key] = $entity_bundle;
    }

    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->create($entity_values);
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressFieldItemList $items */
    $items = $entity->get($addressField->getName());
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $item */
    $item = $items->first() ?: $items->appendItem();

    return $item->getFieldOverrides();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('social_profile.settings')
      ->set('nickname_unique_validation', $form_state->getValue('nickname_unique_validation'))
      ->save();

    /** @var \Drupal\user\RoleInterface $authenticated_role */
    $authenticated_role = Role::load(AccountInterface::AUTHENTICATED_ROLE);
    if ($form_state->getValue("community_visibility.require_verified")) {
      $authenticated_role->revokePermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile profile fields");
    }
    else {
      $authenticated_role->grantPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile profile fields");
    }

    $this->submitFieldsFieldset($form, $form_state);

    // Must run after submitFieldsFieldset because it's dependent on the status.
    $this->submitAddressFieldset($form, $form_state);

    // Most settings in this form are applied to search at run-time. However, in
    // case the visibility of a field has changed for users that can not control
    // their own visibility then we must re-index those profiles. We could be
    // cleverer here but that's more complex than time currently allows so we
    // just mark every profile for re-indexing.
    // We only do this if search is enabled since it's not a mandatory module
    // for profile functionality to work.
    // @todo This should probably be some kind of event that our social_search
    // module can respond to for proper decoupling.
    if ($this->entityTypeManager->hasDefinition("search_api_index")) {
      $indexes = $this->entityTypeManager->getStorage("search_api_index")->loadMultiple();
      foreach ($indexes as $index) {
        $index->getTrackerInstance()->trackAllItemsUpdated("entity:profile");
      }

      $this->messenger()->addStatus(new TranslatableMarkup("Profile data has been scheduled for re-indexing in search to reflect the updated configuration."));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Submit the fields configuration values.
   *
   * This is split out into a separate function to make it easier to find all
   * the other settings this form stores.
   *
   * @param array $form
   *   The settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  private function submitFieldsFieldset(array &$form, FormStateInterface $form_state) : void {
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
      }

      // Show during registration.
      $field_on_registration = (bool) $form_state->getValue(
        ["fields", "list", $field_name, "registration"]
      );
      $stored_on_user_entity = isset(static::$syncedProfileFields[$field_name]);
      // If a field is stored on the user entity then access is controlled by
      // the `AccountForm` class and we can't manage those fields.
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

    // We must always save all roles, even if permissions weren't changed. It's
    // possible that field status was changed which can also affect whether
    // users can edit and create profiles.
    foreach ($roles as $role) {
      $role->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Submit the address sub-field configuration values.
   *
   * This is split out into a separate function to make it easier to find all
   * the other settings this form stores.
   *
   * @param array $form
   *   The settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  private function submitAddressFieldset(array &$form, FormStateInterface $form_state) : void {
    // Type annotation needed for PHPStan until Drupal 9.2.0.
    /** @var \Drupal\field\FieldConfigInterface|NULL $address_field */
    $address_field = FieldConfig::loadByName('profile', 'profile', 'field_profile_address');
    // If the field doesn't exist or a site builder has indicated it should be
    // opted out of our management then we exit early.
    if ($address_field === NULL || $this->fieldManager::isOptedOutOfFieldAccessManagement($address_field)) {
      return;
    }

    // Nothing to save if the address field was disabled.
    if (!$address_field->status()) {
      return;
    }

    $updated_value = $form_state->getValue(['address', 'field_overrides'], [])
      + $form_state->getValue(['address', 'fixed_field_overrides'], []);

    /** @var \Drupal\Core\Field\FieldConfigBase $address_field */
    $address_field
      ->setSetting('field_overrides', $updated_value)
      ->save();

    // Reset the cache to ensure form shows the updated values.
    $this->typedDataManager->clearCachedDefinitions();
  }

}
