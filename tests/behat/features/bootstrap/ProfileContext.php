<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\profile\Entity\Profile;
use Drupal\social_profile\FieldManager;
use Drupal\user\Entity\Role;

/**
 * Defines test steps around user profiles and profile management.
 */
class ProfileContext extends RawMinkContext {

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

      $page->selectFieldOption($field['visibility'] . " visibility for $name field", strtolower($field['visibility']));

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
      $field_ids = \Drupal::entityQuery('field_config')->condition('label', $field_label)->execute();
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
   *   A row representation of the tabe node.
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
