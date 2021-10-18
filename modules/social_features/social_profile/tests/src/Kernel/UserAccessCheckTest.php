<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Tests\social_user\Kernel\UserAccessCheckTest as BaseUserAccessCheckTest;

/**
 * Test access checks for the user entity.
 *
 * This extends the UserAccessCheckTest in the `user` module since that
 * behaviour should remain valid when this module is enabled.
 *
 * @package social_profile
 */
class UserAccessCheckTest extends BaseUserAccessCheckTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Requirements for social_profile.
    "field_group",
    "entity",
    "profile",
    "field",
    "options",
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
    // The module under test.
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
  public function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('profile_type');
    $this->installEntitySchema('profile');
    $this->installConfig('social_profile');
  }

}
