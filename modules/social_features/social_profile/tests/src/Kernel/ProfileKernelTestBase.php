<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for profile related tests.
 */
abstract class ProfileKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    "system",
    "field",
    "social_user",
    "user",
    // Needed to properly set up permissions.
    "entity",
    // User creation in social_user requires a service in role_delegation.
    // @todo Possibly untangle this?
    "role_delegation",
    // Profile is needed for the profile storage.
    "profile",
    // Required for third party config schema.
    "field_group",
    // Modules needed for profile fields.
    "file",
    "image",
    "address",
    "taxonomy",
    "telephone",
    "text",
    "options",
    "filter",
    "lazy",
    "image_widget_crop",
    "crop",
    // The actual module under test.
    "social_profile",
  ];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // @todo when https://www.drupal.org/project/social/issues/3238713 is fixed.
    "core.entity_form_display.profile.profile.default",
    // We don't need views in the GraphQL API so no sense in enabling the views
    // module or validating the schema.
    "views.view.newest_users",
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile_type');
    $this->installEntitySchema('profile');
    $this->installConfig('social_profile');
  }

}
