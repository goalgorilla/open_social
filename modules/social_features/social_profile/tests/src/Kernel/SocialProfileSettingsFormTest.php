<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\profile\Entity\ProfileType;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\social_profile\FieldManager;
use Drupal\social_profile\Form\SocialProfileSettingsForm;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Test the settings form for the social_profile module.
 *
 * This specifically tests the behaviour of the form submit handler, not the
 * structure of the form produced in the build handler.
 */
class SocialProfileSettingsFormTest extends ProfileKernelTestBase {

  use UserCreationTrait;

  /**
   * Open Social's profile profile type.
   */
  private ProfileTypeInterface $defaultProfileType;

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // We don't need views in the GraphQL API so no sense in enabling the views
    // module or validating the schema.
    "views.view.user_admin_people",
    // @todo Fixme.
    "social_profile.settings",
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installConfig(['user', 'social_user']);

    // Create the roles in Open Social so that the form can use it for its
    // configuration table and default values.
    $this->createRole([], 'contentmanager');
    $this->createRole([], 'sitemanager');

    $profileProfileType = ProfileType::load('profile');
    self::assertInstanceOf(ProfileTypeInterface::class, $profileProfileType);
    $this->defaultProfileType = $profileProfileType;
  }

  /**
   * Test the site manager can change default field visibility.
   */
  public function testControlsDefaultFieldVisibility() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    // Set the default value to a known value so we can detect it was changed.
    $visibility_field
      ->setDefaultValue([0 => ['value' => SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE]])
      ->save();

    $field_name = $test_field->getName();

    // The field must be reloaded after every form submission to pull the
    // changes from the database.
    $this->submitForm([
      "fields][list][${field_name}][visibility][default" => SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC,
    ]);
    self::assertEquals(
      [0 => ['value' => SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC]],
      $this->getVisibilityField($test_field)->getDefaultValueLiteral()
    );

    $this->submitForm([
      "fields][list][${field_name}][visibility][default" => SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY,
    ]);
    self::assertEquals(
      [0 => ['value' => SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY]],
      $this->getVisibilityField($test_field)->getDefaultValueLiteral()
    );

    $this->submitForm([
      "fields][list][${field_name}][visibility][default" => SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE,
    ]);
    self::assertEquals(
      [0 => ['value' => SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE]],
      $this->getVisibilityField($test_field)->getDefaultValueLiteral()
    );
  }

  /**
   * Test that the form updates whether the user can edit the visibility field.
   */
  public function testControlsUserCanEditVisibility() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $field_name = $test_field->getName();
    $visibility_field_name = $visibility_field->getName();

    // Check our default state so we know we're actually updating something.
    self::assertFalse($this->getRole('authenticated')->hasPermission("edit own ${visibility_field_name} profile profile field"));

    $this->submitForm([
      "fields][list][${field_name}][visibility][user" => TRUE,
    ]);
    self::assertTrue($this->getRole('authenticated')->hasPermission("edit own ${visibility_field_name} profile profile field"));

    $this->submitForm([
      "fields][list][${field_name}][visibility][user" => FALSE,
    ]);
    self::assertFalse($this->getRole('authenticated')->hasPermission("edit own ${visibility_field_name} profile profile field"));
  }

  /**
   * Test that the form allows configuring who can always see the field.
   *
   * The site manager should always be granted the permission even if the form
   * values submitted for the site manager were false (this happens when the
   * field in the form is disabled).
   */
  public function testAlwaysShowFor() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $field_name = $test_field->getName();
    $visibility_field_name = $visibility_field->getName();

    // Check our default state so we know we're actually updating something.
    self::assertFalse($this->getRole('contentmanager')->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${field_name} profile profile fields"));
    self::assertFalse($this->getRole('sitemanager')->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${field_name} profile profile fields"));

    // Check site manager is forced to TRUE even when not included and that
    // content manager can be enabled.
    $this->submitForm([
      "fields][list][${field_name}][always_show][contentmanager" => TRUE,
    ]);
    self::assertTrue($this->getRole('contentmanager')->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${field_name} profile profile fields"));
    self::assertTrue($this->getRole('sitemanager')->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${field_name} profile profile fields"));

    // Check that content manager can be changed back to false and that site
    // manager is forced to TRUE if submitted as FALSE.
    $this->submitForm([
      "fields][list][${field_name}][always_show][sitemanager" => FALSE,
      "fields][list][${field_name}][always_show][contentmanager" => FALSE,
    ]);
    self::assertFalse($this->getRole('contentmanager')->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${field_name} profile profile fields"));
    self::assertTrue($this->getRole('sitemanager')->hasPermission("view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${field_name} profile profile fields"));
  }

  /**
   * Test that the form controls whether the user can edit the field.
   */
  public function testAllowUserEditing() : void {
    $test_field = $this->createTestProfileField();
    $visibility_field = $this->getVisibilityField($test_field);

    $field_name = $test_field->getName();

    // Check our default state so we know we're actually updating something.
    self::assertFalse($this->getRole('authenticated')->hasPermission("edit own ${field_name} profile profile field"));

    $this->submitForm([
      "fields][list][${field_name}][allow_editing][user" => TRUE,
    ]);
    self::assertTrue($this->getRole('authenticated')->hasPermission("edit own ${field_name} profile profile field"));

    $this->submitForm([
      "fields][list][${field_name}][allow_editing][user" => FALSE,
    ]);
    self::assertFalse($this->getRole('authenticated')->hasPermission("edit own ${field_name} profile profile field"));
  }

  /**
   * Test that the form allows configuring who else can edit the field.
   *
   * The site manager should always be granted the permission even if the form
   * values submitted for the site manager were false (this happens when the
   * field in the form is disabled).
   */
  public function testAllowOtherEditing() : void {
    $test_field = $this->createTestProfileField();

    $field_name = $test_field->getName();

    // Check our default state so we know we're actually updating something.
    self::assertFalse($this->getRole('contentmanager')->hasPermission("edit any ${field_name} profile profile field"));
    self::assertFalse($this->getRole('sitemanager')->hasPermission("edit any ${field_name} profile profile field"));

    // Check site manager is forced to TRUE even when not included and that
    // content manager can be enabled.
    $this->submitForm([
      "fields][list][${field_name}][allow_editing][other][contentmanager" => TRUE,
    ]);
    self::assertTrue($this->getRole('contentmanager')->hasPermission("edit any ${field_name} profile profile field"));
    self::assertTrue($this->getRole('sitemanager')->hasPermission("edit any ${field_name} profile profile field"));

    // Check that content manager can be changed back to false and that site
    // manager is forced to TRUE if submitted as FALSE.
    $this->submitForm([
      "fields][list][${field_name}][allow_editing][other][sitemanager" => FALSE,
      "fields][list][${field_name}][allow_editing][other][contentmanager" => FALSE,
    ]);
    self::assertFalse($this->getRole('contentmanager')->hasPermission("edit any ${field_name} profile profile field"));
    self::assertTrue($this->getRole('sitemanager')->hasPermission("edit any ${field_name} profile profile field"));
  }

  /**
   * Test that the form can control that a field is shown during registration.
   */
  public function testControlsRegister() : void {
    $test_field = $this->createTestProfileField();

    $field_name = $test_field->getName();

    $form_display = EntityFormDisplay::load("profile.profile.register");
    self::assertNotNull($form_display);
    self::assertNull($form_display->getComponent($field_name));

    $this->submitForm([
      "fields][list][${field_name}][registration" => TRUE,
    ]);

    // Must reload to fetch changes.
    $form_display = EntityFormDisplay::load("profile.profile.register");
    self::assertNotNull($form_display);
    self::assertNotNull($form_display->getComponent($field_name));

    $this->submitForm([
      "fields][list][${field_name}][registration" => FALSE,
    ]);
    $form_display = EntityFormDisplay::load("profile.profile.register");
    self::assertNotNull($form_display);
    self::assertNull($form_display->getComponent($field_name));
  }

  /**
   * Test that a field can be disabled.
   */
  public function testControlsStatus() : void {
    $test_field = $this->createTestProfileField();
    $field_name = $test_field->getName();

    // Check baseline and change back and forth.
    self::assertTrue($test_field->status());

    $this->submitForm([
      "fields][list][${field_name}][disabled" => TRUE,
    ]);
    // Must reload field to catch changes.
    $test_field = FieldConfig::loadByName("profile", "profile", $field_name);
    assert($test_field !== NULL);
    self::assertFalse($test_field->status());

    $this->submitForm([
      "fields][list][${field_name}][disabled" => FALSE,
    ]);
    // Must reload field to catch changes.
    $test_field = FieldConfig::loadByName("profile", "profile", $field_name);
    assert($test_field !== NULL);
    self::assertTrue($test_field->status());
  }

  /**
   * Perform a form submission to the form.
   *
   * @param array $user_input
   *   The user input. The key are paths in the form_state values array as
   *   strings with each sub-field separated by `][`.
   */
  private function submitForm(array $user_input) : void {
    $form_state = new FormState();

    $values = [];
    foreach ($user_input as $name => $value) {
      $path = explode("][", $name);
      NestedArray::setValue($values, $path, $value);
    }
    $form_state->setFormState(['values' => $values]);

    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm(SocialProfileSettingsForm::class, $form_state);
  }

  /**
   * Create a field on the profile profile type.
   *
   * Creates a field that can be used for testing. This allows test cases to be
   * independent of the fields included in social_profile.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   *   The created field.
   */
  private function createTestProfileField() : FieldConfigInterface {
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'profile',
      'type' => 'string',
    ]);
    $fieldStorage->save();

    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => $this->defaultProfileType->id(),
    ]);
    $field->save();

    return $field;
  }

  /**
   * Get the visibility field config related to this value field.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $fieldConfig
   *   The value field to load the visibility field for.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   *   The visibility field config.
   */
  private function getVisibilityField(FieldConfigInterface $fieldConfig) : FieldConfigInterface {
    $fieldName = FieldManager::getVisibilityFieldName($fieldConfig);
    self::assertNotNull($fieldName, "Could not get visibility field name for field config.");

    $bundle = $fieldConfig->getTargetBundle();
    self::assertNotNull($bundle, "Field config is not attached to bundle of entity.");

    $visibilityField = FieldConfig::loadByName($fieldConfig->getTargetEntityTypeId(), $bundle, $fieldName);
    self::assertNotNull($visibilityField, "Could not load visibility field for field config.");

    return $visibilityField;
  }

  /**
   * Load the role asserting that it exists.
   *
   * @param string $name
   *   The role id.
   *
   * @return \Drupal\user\RoleInterface
   *   The loaded role.
   */
  private function getRole(string $name) : RoleInterface {
    $role = Role::load($name);
    self::assertInstanceOf(RoleInterface::class, $role, "Could not load role: '$name'.");
    return $role;
  }

}
