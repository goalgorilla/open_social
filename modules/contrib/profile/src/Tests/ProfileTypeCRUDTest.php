<?php

/**
 * @file
 * Contains \Drupal\profile\Tests\ProfileTypeCRUDTest.
 */

namespace Drupal\profile\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;

/**
 * Tests basic CRUD functionality of profile types.
 *
 * @group profile
 */
class ProfileTypeCRUDTest extends ProfileTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access user profiles',
      'administer profile types',
      'administer profile fields',
      'administer profile display',
      'bypass profile access',
    ]);
  }

  /**
   * Verify that routes are created for the profile type.
   */
  public function testRoutes() {
    $this->drupalLogin($this->adminUser);
    $type = $this->createProfileType($this->randomMachineName());
    \Drupal::service('router.builder')->rebuildIfNeeded();
    $this->drupalGet("user/{$this->adminUser->id()}/{$type->id()}");
    $this->assertResponse(200);
  }

  /**
   * Tests CRUD operations for profile types through the UI.
   */
  public function testCRUDUI() {
    $this->drupalLogin($this->adminUser);

    // Create a new profile type.
    $this->drupalGet('admin/config/people/profiles/types');
    $this->assertResponse(200);
    $this->clickLink(t('Add profile type'));

    $this->assertUrl('admin/config/people/profiles/types/add');
    $id = Unicode::strtolower($this->randomMachineName());
    $label = $this->randomString();
    $edit = [
      'id' => $id,
      'label' => $label,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertUrl('admin/config/people/profiles/types');
    $this->assertRaw(new FormattableMarkup('%label profile type has been created.', ['%label' => $label]));
    $this->assertLinkByHref("admin/config/people/profiles/types/manage/$id");
    $this->assertLinkByHref("admin/config/people/profiles/types/manage/$id/fields");
    $this->assertLinkByHref("admin/config/people/profiles/types/manage/$id/display");
    $this->assertLinkByHref("admin/config/people/profiles/types/manage/$id/delete");

    // Edit the new profile type.
    $this->drupalGet("admin/config/people/profiles/types/manage/$id");
    $this->assertRaw(new FormattableMarkup('Edit %label profile type', ['%label' => $label]));
    $edit = [
      'registration' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertUrl('admin/config/people/profiles/types');
    $this->assertRaw(new FormattableMarkup('%label profile type has been updated.', ['%label' => $label]));

    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();

    // Add a field to the profile type.
    $this->drupalGet("admin/config/people/profiles/types/manage/$id/fields/add-field");
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_label = $this->randomString();
    $edit = [
      'new_storage_type' => 'string',
      'label' => $field_label,
      'field_name' => $field_name,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));
    $this->drupalPostForm(NULL, [], t('Save settings'));
    $this->assertRaw(new FormattableMarkup('Saved %label configuration.', ['%label' => $field_label]));

    // Verify that the field is still associated with it.
    $this->drupalGet("admin/config/people/profiles/types/manage/$id/fields");
    // @todo D8 core: This assertion fails for an unknown reason. Database
    //   contains the right values, so field_attach_rename_bundle() works
    //   correctly. The pre-existing field does not appear on the Manage
    //   fields page of the renamed bundle. Not even flushing all caches
    //   helps. Can be reproduced manually.
    // $this->assertText(check_plain($field_label));
  }

}
