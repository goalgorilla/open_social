<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\field\Entity\FieldConfig;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_profile\FieldManager;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Defines test steps around user profiles and profile management.
 */
class ProfileContext extends RawMinkContext {

  use EntityTrait;

  /**
   * Fields that were enabled during the current scenario.
   *
   * @var string[]
   */
  private array $fieldsEnabled = [];

  /**
   * Fields that were disabled during the current scenario.
   *
   * @var string[]
   */
  private array $fieldsDisabled = [];

  /**
   * Field settings that have been changed during the current scenario.
   */
  private array $fieldSettings;

  /**
   * The Drupal context which gives us access to user management.
   */
  private DrupalContext $drupalContext;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
  }

  /**
   * Go to the profile page for the current user.
   *
   * @When I am viewing my profile
   */
  public function amViewingMyProfile() : void {
    $user_id = $this->drupalContext->getUserManager()->getCurrentUser()->uid;
    $this->visitPath("/user/$user_id");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Try to view a specific profile even if you might not have access.
   *
   * @When I try to view the profile of :user
   */
  public function attemptViewingProfile(string $user) : void {
    if ($user === 'anonymous') {
      $user_ids = [0];
    }
    else {
      $user_ids = \Drupal::entityQuery('user')
        ->accessCheck(FALSE)
        ->condition('name', $user)
        ->execute();

      if (count($user_ids) !== 1) {
        throw new \InvalidArgumentException("Could not find user with username `$user'.");
      }
    }

    $user_id = reset($user_ids);
    $this->visitPath("/user/$user_id");
  }

  /**
   * Create or update the profile for a user with a specific nickname.
   *
   * Updates a profile in the form:
   * | field_profile_first_name | John |
   * | field_profile_last_name  | Doe  |
   *
   * @Given user :username has a profile filled with:
   */
  public function userHasProfile(string $username, TableNode $profileTable) : void {
    $profile = $profileTable->getRowsHash();
    $profile['owner'] = $username;
    $this->profileUpdate($profile);
  }

  /**
   * Create or update the profile for the current user.
   *
   * Updates a profile in the form:
   * | field_profile_first_name | John |
   * | field_profile_last_name  | Doe  |
   *
   * @Given I have a profile filled with:
   * @Given have a profile filled with:
   */
  public function iHaveProfile(TableNode $profileTable) : void {
    $profile = $profileTable->getRowsHash();
    if (isset($profile['uid'])) {
      throw new \InvalidArgumentException("Should not set `uid` for profile, use 'user :username has a profile filled with' instead.");
    }
    if (isset($profile['owner'])) {
      throw new \InvalidArgumentException("Should not set `owner` for profile, use 'user :username has a profile filled with' instead.");
    }
    $this->profileUpdate($profile);
  }


  /**
   * Go to the profile edit page for the current user.
   *
   * @When I am editing my profile
   */
  public function amEditingMyProfile() : void {
    $user_id = $this->drupalContext->getUserManager()->getCurrentUser()->uid;
    $this->visitPath("/user/$user_id/profile");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Go to the profile edit page for the specified user.
   *
   * @When I try to edit the profile of :user
   * @When I try to edit my profile
   */
  public function amEditingProfileOf(?string $user = NULL) : void {
    if ($user === NULL) {
      $user_id = $this->drupalContext->getUserManager()->getCurrentUser()->uid;
    }
    elseif ($user === 'anonymous') {
      $user_id = 0;
    }
    else {
      $user_ids = \Drupal::entityQuery('user')
        ->accessCheck(FALSE)
        ->condition('name', $user)
        ->execute();

      if (count($user_ids) !== 1) {
        throw new \InvalidArgumentException("Could not find user with username `$user'.");
      }

      $user_id = reset($user_ids);
    }

    $this->visitPath("/user/$user_id/profile");
  }

  /**
   * Manage whether unique nicknames are enforced.
   *
   * @param string $state
   *   enabled or disabled.
   *
   * @Given unique nicknames for users is :state
   */
  public function setUniqueNicknames(string $state) : void {
    assert($state === "enabled" || $state === "disabled", ":state must be one of 'enabled' or 'disabled' (got '$state')");

    \Drupal::configFactory()->getEditable("social_profile.settings")->set("nickname_unique_validation", $state === "enabled")->save();
    \Drupal::service("entity_field.manager")->clearCachedFieldDefinitions();
  }

  /**
   * Check the state of unique nicknames enforcement.
   *
   * @param string $state
   *   enabled or disabled.
   *
   * @Then unique nicknames for users should be :state
   */
  public function assertUniqueNicknames(string $state) : void {
    assert($state === "enabled" || $state === "disabled", ":state must be one of 'enabled' or 'disabled' (got '$state')");
    // For some reason the way Drupal Extension runs Drupal the permissions get
    // cached in our runtime, so we need to cache bust to ensure we can actually
    // see the result of the form save.
    \Drupal::configFactory()->clearStaticCache();

    $expectedState = $state === "enabled";
    $actualState = \Drupal::config("social_profile.settings")->get("nickname_unique_validation");
    if ($expectedState !== $actualState) {
      throw new \RuntimeException("Expected unique nicknames to be $state but got '" . ($actualState ? "enabled" : "disabled") . "'");
    }
  }

  /**
   * Enable or disable fields on the profile fields form.
   *
   * Will check or uncheck the "Disabled" field for the given fields.
   *
   * I disable the fields on the profile fields form:
   * | Field name |
   * | Expertise  |
   * | ...        |
   *
   * @When I :action the fields on the profile fields form:
   */
  public function enableDisableProfileFields(string $action, TableNode $fields) : void {
    assert($action === "enable" || $action === "disable", ":action must be one of 'enable' or 'disable' (got '$action')");
    foreach ($fields->getHash() as $field) {
      if ($action === "disable") {
        $this->fieldsDisabled[] = $field['Field name'];
        $this->getSession()->getPage()->checkField($field['Field name'] . " Disabled");
      }
      else {
        $this->fieldsEnabled[] = $field['Field name'];
        $this->getSession()->getPage()->uncheckField($field['Field name'] . " Disabled");
      }
    }
  }

  /**
   * FIll in the fields on the profile fields settings form.
   *
   * I fill in the profile fields form with:
   * | Field name | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | User can edit value | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
   * | Address    | Private    | true                     | true                            | false                         | true                | false                            | false                          | true                 | true     |
   * | Function   | Public     | false                    | false                           | false                         | true                | true                             | false                          | false                | false    |
   * | ...        | ...        | ...                      | ...                             | ...                           | ...                 | ...                              | ...                            | ...                  | ...      |
   *
   * @When I fill in the profile fields form with:
   * @When fill in the profile fields form with:
   */
  public function fillProfileFieldsSettingsForm(TableNode $rawFields) : void {
    $this->fieldSettings = $fields = $this->parseFieldSettingsTableNode($rawFields);

    $page = $this->getSession()->getPage();

    foreach ($fields as $field) {
      $name = $field['field_name'];

      $page->selectFieldOption($field['visibility'] . " visibility for $name", strtolower($field['visibility']));

      if ($field['user_edit_visibility']) {
        $page->checkField("User can edit $name visibility");
      }
      else {
        $page->uncheckField("User can edit $name visibility");
      }

      if ($field['always_show_content_manager']) {
        $page->checkField("Always show $name field for Content manager");
      }
      else {
        $page->uncheckField("Always show $name field for Content manager");
      }

      if ($field['always_show_verified_user']) {
        $page->checkField("Always show $name field for Verified user");
      }
      else {
        $page->uncheckField("Always show $name field for Verified user");
      }

      if ($field['user_edit_value']) {
        $page->checkField("User can edit $name field value");
      }
      else {
        $page->uncheckField("User can edit $name field value");
      }

      if ($field['allow_editing_content_manager']) {
        $page->checkField("Allow editing $name field by Content manager");
      }
      else {
        $page->uncheckField("Allow editing $name field by Content manager");
      }

      if ($field['allow_editing_verified_user']) {
        $page->checkField("Allow editing $name field by Verified user");
      }
      else {
        $page->uncheckField("Allow editing $name field by Verified user");
      }

      if ($field['registration']) {
        $page->checkField("Show $name field At registration");
      }
      else {
        $page->uncheckField("Show $name field At registration");
      }

      if ($field['required']) {
        $page->checkField("$name field is Required");
      }
      else {
        $page->uncheckField("$name field is Required");
      }
    }
  }

  /**
   * Checks that profile field settings set in previous steps are saved.
   *
   * @Then the profile field settings should be updated
   */
  public function profileFieldSettingsShouldBeUpdated() : void {
    // For some reason the way Drupal Extension runs Drupal the permissions get
    // cached in our runtime, so we need to cache bust to ensure we can actually
    // see the result of the form save.
    \Drupal::configFactory()->clearStaticCache();
    \Drupal::entityTypeManager()->getStorage('user_role')->resetCache();

    $fieldManager = \Drupal::service('social_profile.field_manager');
    assert($fieldManager instanceof FieldManager, "Could not load field manager service");

    $authenticated_role = Role::load(Role::AUTHENTICATED_ID);
    assert($authenticated_role !== NULL);
    $verified_role = Role::load("verified");
    assert($verified_role !== NULL);
    $contentmanager_role = Role::load("contentmanager");
    assert($contentmanager_role !== NULL);
    $sitemanager_role = Role::load("sitemanager");
    assert($sitemanager_role !== NULL);

    $registration_user_form_display = EntityFormDisplay::load("user.user.register");
    assert($registration_user_form_display !== NULL);
    $registration_profile_form_display = EntityFormDisplay::load("profile.profile.register");
    assert($registration_profile_form_display !== NULL);

    if (count($this->fieldsDisabled) !== 0) {
      $disabledFields = \Drupal::entityQuery('field_config')
        ->condition('entity_type', 'profile')
        ->condition('bundle', 'profile')
        ->condition('label', $this->fieldsDisabled, 'IN')
        ->execute();
      assert(count($disabledFields) === count($this->fieldsDisabled), "Could not load all fields that were disabled by their field label.");

      foreach (FieldConfig::loadMultiple($disabledFields) as $field) {
        if ($field->status()) {
          throw new \RuntimeException("Field {$field->id()} should have been disabled but was found enabled.");
        }
      }
    }

    if (count($this->fieldsEnabled) !== 0) {
      $enabledFields = \Drupal::entityQuery('field_config')
        ->condition('entity_type', 'profile')
        ->condition('bundle', 'profile')
        ->condition('label', $this->fieldsEnabled, 'IN')
        ->execute();
      assert(count($enabledFields) === count($this->fieldsEnabled), "Could not load all fields that were disabled by their field label.");

      foreach (FieldConfig::loadMultiple($enabledFields) as $field) {
        if (!$field->status()) {
          throw new \RuntimeException("Field {$field->id()} should have been enabled but was found disabled.");
        }
      }
    }

    foreach ($this->fieldSettings as $fieldSettings) {
      $field_label =  $fieldSettings['field_name'];
      $field_ids = \Drupal::entityQuery('field_config')
        ->condition('entity_type', 'profile')
        ->condition('bundle', 'profile')
        ->condition('label', $field_label)
        ->execute();
      assert(count($field_ids) === 1, "Could not find a unique field with field name $field_label");
      $field_id = end($field_ids);

      $field = FieldConfig::load($field_id);
      assert($field !== NULL);
      $visibility_field = FieldConfig::loadByName("profile", "profile", $fieldManager::getVisibilityFieldName($field));
      assert($visibility_field !== NULL, "Could not load visibility field for $field_id");

      $default_visibility_value = $visibility_field->getDefaultValue(Profile::create(['type' => 'profile']))[0]['value'];
      $visibility_setting = strtolower($fieldSettings['visibility']);
      if ($default_visibility_value !== $visibility_setting) {
        throw new \RuntimeException("Expected visibility default value for $field_label field to be '$visibility_setting' but got '$default_visibility_value'.");
      }

      $expected_permission = "edit own {$visibility_field->getName()} profile profile field";
      if ($authenticated_role->hasPermission($expected_permission) !== $fieldSettings['user_edit_visibility']) {
        throw new \RuntimeException("Expected authenticated role to " . ($fieldSettings['user_edit_visibility'] ? "have" : "not have") . " the '$expected_permission' permission (field {$field->getName()}).");
      }

      $expected_permission = "view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " {$field->getName()} profile profile fields";
      if ($verified_role->hasPermission($expected_permission) !== $fieldSettings['always_show_verified_user']) {
        throw new \RuntimeException("Expected verified role to " . ($fieldSettings['always_show_verified_user'] ? "have" : "not have") . " the '$expected_permission' permission.");
      }

      $expected_permission = "view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " {$field->getName()} profile profile fields";
      if ($contentmanager_role->hasPermission($expected_permission) !== $fieldSettings['always_show_content_manager']) {
        throw new \RuntimeException("Expected contentmanager role to " . ($fieldSettings['always_show_content_manager'] ? "have" : "not have") . " the '$expected_permission' permission.");
      }

      $expected_permission = "view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " {$field->getName()} profile profile fields";
      if (!$sitemanager_role->hasPermission($expected_permission)) {
        throw new \RuntimeException("Expected sitemanager role to have the '$expected_permission' permission.");
      }

      $expected_permission = "edit own {$field->getName()} profile profile field";
      if (!$authenticated_role->hasPermission($expected_permission)) {
        throw new \RuntimeException("Expected authenticated role to " . ($fieldSettings['user_edit_value'] ? "have" : "not have") . " the '$expected_permission' permission.");
      }

      $expected_permission = "edit any {$field->getName()} profile profile field";
      if ($verified_role->hasPermission($expected_permission) !== $fieldSettings['allow_editing_verified_user']) {
        throw new \RuntimeException("Expected verified role to " . ($fieldSettings['allow_editing_verified_user'] ? "have" : "not have") . " the '$expected_permission' permission.");
      }

      $expected_permission = "edit any {$field->getName()} profile profile field";
      if ($contentmanager_role->hasPermission($expected_permission) !== $fieldSettings['allow_editing_content_manager']) {
        throw new \RuntimeException("Expected contentmanager role to " . ($fieldSettings['allow_editing_content_manager'] ? "have" : "not have") . " the '$expected_permission' permission.");
      }

      $expected_permission = "edit any {$field->getName()} profile profile field";
      if (!$sitemanager_role->hasPermission($expected_permission)) {
        throw new \RuntimeException("Expected sitemanager role to have the '$expected_permission' permission.");
      }

      $stored_on_user_entity = isset($syncedProfileFields[$field->getName()]);

      $shown_at_registration = $stored_on_user_entity
          ? $registration_user_form_display->getComponent($syncedProfileFields[$field->getName()]) !== NULL
          : $registration_profile_form_display->getComponent($field->getName()) !== NULL;
      // Email should always be shown at registration.
      $should_be_shown = $field->getName() === "field_profile_email" || $fieldSettings['registration'];

      if ($shown_at_registration !== $should_be_shown) {
        throw new \RuntimeException("Expected $field_label " . ($should_be_shown ? "to be" : "not to be" /* that is the question */) . " shown at registration.");
      }

      if ($field->isRequired() !== $fieldSettings['required']) {
        throw new \RuntimeException("Expected $field_label " . ($fieldSettings['required'] ? "to be" : "not to be") . " required.");
      }
    }
  }

  /**
   * Enable or disable all fields in the profile fields configuration.
   *
   * @Given all profile fields are :action
   */
  public function setAllProfileFieldsStatus(string $action) : void {
    assert($action === "enabled" || $action === "disabled", ":action must be one of 'enabled' or 'disabled' (got '$action')");
    $field_manager = \Drupal::service("social_profile.field_manager");
    assert($field_manager instanceof FieldManager);
    foreach (FieldConfig::loadMultiple() as $fieldConfig) {
      if ($field_manager::isOptedOutOfFieldAccessManagement($fieldConfig)) {
        continue;
      }

      $fieldConfig->setStatus($action === "enabled")->save();
    }

    // We must trigger a role update here for tests to work.
    // @todo This proves that we need to move the config changes to a service
    // that can do this. This workaround works as long as site managers change
    // config through the profile form, which will save all roles regardless of
    // what was changed, but that won't work through the API.
    foreach (Role::loadMultiple() as $role) {
      $role->save();
    }
  }

  /**
   * Enable or disable fields in the profile fields configuration.
   *
   * the profile fields are disabled:
   * | Field name |
   * | Expertise  |
   * | ...        |
   *
   * @Given the profile fields are :action:
   */
  public function setProfileFieldsStatus(string $action, TableNode $fields) : void {
    assert($action === "enabled" || $action === "disabled", ":action must be one of 'enabled' or 'disabled' (got '$action')");
    foreach ($fields->getHash() as $field) {
      $field_ids = \Drupal::entityQuery('field_config')
        ->condition('entity_type', 'profile')
        ->condition('bundle', 'profile')
        ->condition('label', $field['Field name'])
        ->execute();
      assert(count($field_ids) === 1, "Could not find a unique field with field label {$field['Field name']}");
      $config = FieldConfig::load(end($field_ids));
      assert($config !== NULL);

      if ($action === "disabled") {
        $config->setStatus(FALSE);
      }
      else {
        $config->setStatus(TRUE);
      }
      $config->save();
    }

    // We must trigger a role update here for tests to work.
    // @todo This proves that we need to move the config changes to a service
    // that can do this. This workaround works as long as site managers change
    // config through the profile form, which will save all roles regardless of
    // what was changed, but that won't work through the API.
    foreach (Role::loadMultiple() as $role) {
      $role->save();
    }
  }

  /**
   * Set the profile field settings to a specific state.
   *
   * the profile field settings:
   * | Field name | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | User can edit value | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
   * | Address    | Private    | true                     | true                            | false                         | true                | false                            | false                          | true                 | true     |
   * | Function   | Public     | false                    | false                           | false                         | true                | true                             | false                          | false                | false    |
   * | ...        | ...        | ...                      | ...                             | ...                           | ...                 | ...                              | ...                            | ...                  | ...      |
   *
   * @Given the profile field settings:
   */
  public function setProfileFieldSettings(TableNode $rawFields) : void {
    $fieldManager = \Drupal::service('social_profile.field_manager');
    assert($fieldManager instanceof FieldManager, "Could not load field manager service");

    $authenticated_role = Role::load(Role::AUTHENTICATED_ID);
    assert($authenticated_role !== NULL);
    $verified_role = Role::load("verified");
    assert($verified_role !== NULL);
    $contentmanager_role = Role::load("contentmanager");
    assert($contentmanager_role !== NULL);

    $registration_profile_form_display = EntityFormDisplay::load("profile.profile.register");
    assert($registration_profile_form_display !== NULL);

    $fields = $this->parseFieldSettingsTableNode($rawFields);

    foreach ($fields as $fieldSettings) {
      $field_label =  $fieldSettings['field_name'];
      $field_ids = \Drupal::entityQuery('field_config')
        ->condition('entity_type', 'profile')
        ->condition('bundle', 'profile')
        ->condition('label', $field_label)
        ->execute();
      assert(count($field_ids) === 1, "Could not find a unique field with field label $field_label");
      $field_id = end($field_ids);

      $field = FieldConfig::load($field_id);
      assert($field !== NULL);
      $visibility_field = FieldConfig::loadByName("profile", "profile", $fieldManager::getVisibilityFieldName($field));
      assert($visibility_field !== NULL, "Could not load visibility field for $field_id");

      $visibility_field
        ->setDefaultValue(strtolower($fieldSettings['visibility']))
        ->save();

      if ($fieldSettings['user_edit_visibility']) {
        $authenticated_role->grantPermission("edit own {$visibility_field->getName()} profile profile field");
      }
      else {
        $authenticated_role->revokePermission("edit own {$visibility_field->getName()} profile profile field");
      }

      if ($fieldSettings['always_show_verified_user']) {
        $verified_role->grantPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " {$field->getName()} profile profile fields");
      }
      else {
        $verified_role->revokePermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " {$field->getName()} profile profile fields");
      }

      if ($fieldSettings['always_show_content_manager']) {
        $contentmanager_role->grantPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " {$field->getName()} profile profile fields");
      }
      else {
        $contentmanager_role->revokePermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " {$field->getName()} profile profile fields");
      }

      if ($fieldSettings['user_edit_value']) {
        $authenticated_role->grantPermission("edit own {$field->getName()} profile profile field");
      }
      else {
        $authenticated_role->revokePermission("edit own {$field->getName()} profile profile field");
      }

      if ($fieldSettings['allow_editing_verified_user']) {
        $verified_role->grantPermission("edit any {$field->getName()} profile profile field");
      }
      else {
        $verified_role->revokePermission("edit any {$field->getName()} profile profile field");
      }

      if ($fieldSettings['allow_editing_content_manager']) {
        $contentmanager_role->grantPermission("edit any {$field->getName()} profile profile field");
      }
      else {
        $contentmanager_role->revokePermission("edit any {$field->getName()} profile profile field");
      }

      $syncedProfileFields = [
        "field_profile_email" => "mail",
        "field_profile_preferred_language" => "language",
      ];
      $field_on_registration = $fieldSettings['registration'];
      $stored_on_user_entity = isset($syncedProfileFields[$field->getName()]);
      // If a field is stored on the user entity then access is controlled by
      // the `AccountForm` class and we can't manage those fields.
      if (!$stored_on_user_entity) {
        if (!$field_on_registration && $registration_profile_form_display->getComponent($field->getName()) !== NULL) {
          $registration_profile_form_display->removeComponent($field->getName());
        }
        elseif ($field_on_registration && $registration_profile_form_display->getComponent($field->getName()) === NULL) {
          $default_profile_form_display = EntityFormDisplay::load("profile.profile.default");
          assert($default_profile_form_display !== NULL);

          // Use the same settings as on the default form. This ensures a
          // consistent order (weight) and consistent choice of widget.
          $default_form_component = $default_profile_form_display->getComponent($field->getName());

          $registration_profile_form_display->setComponent(
            $field->getName(),
            $default_form_component ?? []
          );
        }
      }

      $field->setRequired($fieldSettings['required']);

      // Store all the things that changed.
      $field->save();
      $authenticated_role->save();
      $verified_role->save();
      $contentmanager_role->save();
      $registration_profile_form_display->save();
    }
  }

  /**
   * Update a profile for a user.
   *
   * @param array $profile
   *   The field values for the profile.
   *
   * @return \Drupal\profile\Entity\Profile
   *   The updated profile.
   */
  private function profileUpdate(array $profile) : Profile {
    if (!isset($profile['owner'])) {
      $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
      $profile['uid'] ??= is_object($current_user) ? $current_user->uid ?? 0 : 0;
    }
    else {
      $account = user_load_by_name($profile['owner']);
      if ($account->id() !== 0) {
        $profile['uid'] ??= $account->id();
      }
      else {
        throw new \Exception(sprintf("User with username '%s' does not exist.", $profile['owner']));
      }
      unset($profile['owner']);
    }

    if ($profile['uid'] === 0) {
      throw new \InvalidArgumentException("Can not update the profile of the anonymous user");
    }

    $profile['type'] = 'profile';
    $this->validateEntityFields("profile", $profile);
    $profile_object = \Drupal::entityTypeManager()->getStorage('profile')->loadByUser(User::load($profile['uid']), 'profile');
    if ($profile_object instanceof ProfileInterface) {
      foreach ($profile as $field => $value) {
        $profile_object->set($field, $value);
      }
    }
    else {
      $profile_object = Profile::create($profile);
    }

    $violations = $profile_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The profile you tried to update is invalid: $violations");
    }
    $profile_object->save();

    return $profile_object;
  }

  /**
   * Parse a table node for profile settings into a field with system names.
   *
   * This allows us to keep the labels human readable in our tests but have a
   * simpler internal representation. It also means we can change the labels if
   * we find others are clearer without changing other places of our context.
   *
   * @param \Behat\Gherkin\Node\TableNode $rawFields
   *   The table node as provided by behat.
   *
   * @return array
   *   A row representation of the table node.
   */
  private function parseFieldSettingsTableNode(TableNode $rawFields) : array {
    $fields = [];
    foreach ($rawFields->getHash() as $field) {
      $name = $field['Field name'];
      assert(!empty($name), "Missing Field name");
      // First letter of visibility must be upper case since we deal with labels
      // where that's the case, but the system names are all lowercase.
      $visibility = $field['Visibility'];
      $allowed_visibility = array_map(
        "ucfirst",
        [
          SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC,
          SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY,
          SOCIAL_PROFILE_FIELD_VISIBILITY_FRIENDS,
          SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE,
        ]
      );
      assert(in_array($visibility, $allowed_visibility, TRUE), "Expected Visibility to be one of " . implode(", ", $allowed_visibility) . ". Got '{$field['Visibility']}'.");

      $fields[] = [
        'field_name' => $name,
        'visibility' => $visibility,
        'user_edit_visibility' => $this->requireColumnAsBool($field['User can edit visibility'], "For 'User can edit visibility' of $name"),
        'always_show_content_manager' => $this->requireColumnAsBool($field['Always show for Content manager'], "For 'Always show for Content manager' of $name"),
        'always_show_verified_user' => $this->requireColumnAsBool($field['Always show for Verified user'], "For 'Always show for Verified user' of $name"),
        'user_edit_value' => $this->requireColumnAsBool($field['User can edit value'], "For 'User can edit value' of $name"),
        'allow_editing_content_manager' => $this->requireColumnAsBool($field['Allow editing by Content manager'], "For 'Allow editing by Content manager' of $name"),
        'allow_editing_verified_user' => $this->requireColumnAsBool($field['Allow editing by verified user'], "For 'Allow editing by verified user' of $name"),
        'registration' => $this->requireColumnAsBool($field['Show at registration'], "For 'Show at registration' of $name"),
        'required' => $this->requireColumnAsBool($field['Required'], "For 'Required' of $name"),
      ];

      // Unset all the values we've used.
      unset(
        $field['Field name'],
        $field['Visibility'],
        $field['User can edit visibility'],
        $field['Always show for Content manager'],
        $field['Always show for Verified user'],
        $field['User can edit value'],
        $field['Allow editing by Content manager'],
        $field['Allow editing by verified user'],
        $field['Show at registration'],
        $field['Required']
      );

      // Warn if a user is using columns they didn't intend as it may mean they
      // made a typo.
      if (count($field) !== 0) {
        throw new \InvalidArgumentException("Unexpected columns: '" . implode("', '", array_keys($field)) . "'");
      }
    }

    return $fields;
  }

  /**
   * Converts a TableNode column value to a boolean.
   *
   * TableNodes in Behat do not do any processing but we may want a more
   * readable input than 1 or 0 (and make it less likely someone has to read the
   * docs). We can accept similar values to what we can put in YAML files:
   * '1', 'true', 'yes', 'on', '0', 'false', 'no', 'off', or ''.
   *
   * @param string $value
   *   The user input value.
   * @param string $description
   *   An optional description to provide users more info in case of failure.
   *
   * @return bool
   *   The parsed boolean value.
   *
   * @throws \RuntimeException
   *   In case the input was not a valid boolean input.
   */
  protected function requireColumnAsBool(string $value, string $description = "") : bool {
    $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($filtered === NULL) {
      throw new \InvalidArgumentException("Value must be on of '1', 'true', 'yes', 'on', '0', 'false', 'no', 'off', or ''. Got '$value'. " . $description);
    }
    return $filtered;
  }

}
