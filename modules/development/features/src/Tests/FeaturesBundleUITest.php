<?php

/**
 * @file
 * Contains \Drupal\features\Tests\FeaturesBundleUITest.
 */

namespace Drupal\features\Tests;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\features\FeaturesBundleInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests configuring bundles.
 *
 * @group features
 */
class FeaturesBundleUITest extends WebTestBase {
  use StringTranslationTrait;

  /**
   * @todo Remove the disabled strict config schema checking.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'features', 'features_ui'];

  /**
   * The features bundle storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface $bundleStorage
   */
  protected $bundleStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->bundleStorage = \Drupal::entityTypeManager()->getStorage('features_bundle');

    $admin_user = $this->createUser(['administer site configuration', 'export configuration', 'administer modules']);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Get the default features bundle.
   *
   * @return \Drupal\features\FeaturesBundleInterface
   *   The features bundle.
   */
  protected function defaultBundle() {
    return $this->bundleStorage->load('default');
  }

  /**
   * Completely remove a features assignment method from the bundle.
   *
   * @param string $method_id
   *   The assignment method ID.
   */
  protected function removeAssignment($method_id) {
    $bundle = $this->defaultBundle();
    $assignments = $bundle->get('assignments');
    unset($assignments[$method_id]);
    $bundle->set('assignments', $assignments);
    $bundle->save();
  }

  /**
   * Tests configuring an assignment.
   */
  public function testAssignmentConfigure() {
    // Check initial values.
    $settings = $this->defaultBundle()->getAssignmentSettings('exclude');
    $this->assertTrue(isset($settings['types']['config']['features_bundle']), 'Excluding features_bundle');
    $this->assertFalse(isset($settings['types']['config']['system_simple']), 'Not excluding system_simple');
    $this->assertFalse(isset($settings['types']['config']['user_role']), 'Not excluding user_role');
    $this->assertTrue($settings['curated'], 'Excluding curated items');
    $this->assertTrue($settings['module']['namespace'], 'Excluding by namespace');

    // Check initial form.
    $this->drupalGet('admin/config/development/features/bundle/_exclude/default');
    $this->assertFieldChecked('edit-types-config-features-bundle', 'features_bundle is checked');
    $this->assertNoFieldChecked('edit-types-config-system-simple', 'system_simple is not checked');
    $this->assertNoFieldChecked('edit-types-config-user-role', 'user_role is not checked');
    $this->assertFieldChecked('edit-curated', 'curated is checked');
    $this->assertFieldChecked('edit-module-namespace', 'namespace is checked');

    // Configure the form.
    $this->drupalPostForm(NULL, [
      'types[config][system_simple]' => TRUE,
      'types[config][user_role]' => FALSE,
      'curated' => TRUE,
      'module[namespace]' => FALSE,
    ], $this->t('Save settings'));

    // Check form results.
    $this->drupalGet('admin/config/development/features/bundle/_exclude/default');
    $this->assertFieldChecked('edit-types-config-features-bundle', 'Saved, features_bundle is checked');
    $this->assertFieldChecked('edit-types-config-system-simple', 'Saved, system_simple is checked');
    $this->assertNoFieldChecked('edit-types-config-user-role', 'Saved, user_role is not checked');
    $this->assertFieldChecked('edit-curated', 'Saved, curated is checked');
    $this->assertNoFieldChecked('edit-module-namespace', 'Saved, namespace is not checked');

    // Check final values.
    $settings = $this->defaultBundle()->getAssignmentSettings('exclude');
    $this->assertTrue(isset($settings['types']['config']['features_bundle']), 'Saved, excluding features_bundle');
    $this->assertTrue(isset($settings['types']['config']['system_simple']), 'Saved, excluding system_simple');
    $this->assertFalse(isset($settings['types']['config']['user_role']), 'Saved, not excluding user_role');
    $this->assertTrue($settings['curated'], 'Saved, excluding curated items');
    $this->assertFalse($settings['module']['namespace'], 'Saved, not excluding by namespace');
  }

  /**
   * Tests configuring an assignment that didn't exist before.
   */
  public function testNewAssignmentConfigure() {
    $this->removeAssignment('exclude');

    // Is it really removed?
    $all_settings = $this->defaultBundle()->getAssignmentSettings();
    $this->assertFalse(isset($all_settings['exclude']), 'Exclude plugin is unknown');

    // Can still get settings.
    $settings = $this->defaultBundle()->getAssignmentSettings('exclude');
    $this->assertFalse($settings['enabled'], 'Disabled exclude plugin');
    $this->assertFalse(isset($settings['types']['config']['features_bundle']), 'Not excluding features_bundle');
    $this->assertFalse(isset($settings['types']['config']['system_simple']), 'Not excluding system_simple');
    $this->assertFalse(isset($settings['types']['config']['user_role']), 'Not excluding user_role');
    $this->assertFalse($settings['curated'], 'Not excluding curated items');
    $this->assertFalse($settings['module']['namespace'], 'Not excluding by namespace');

    // Can we visit the config page with no settings?
    $this->drupalGet('admin/config/development/features/bundle/_exclude/default');
    $this->assertNoFieldChecked('edit-types-config-features-bundle', 'features_bundle is not checked');
    $this->assertNoFieldChecked('edit-types-config-system-simple', 'system_simple is not checked');
    $this->assertNoFieldChecked('edit-types-config-user-role', 'user_role is not checked');
    $this->assertNoFieldChecked('edit-curated', 'curated is not checked');
    $this->assertNoFieldChecked('edit-module-namespace', 'namespace is not checked');

    // Can we enable the method?
    $this->drupalGet('admin/config/development/features/bundle');
    $this->assertNoFieldChecked('edit-enabled-exclude', 'Exclude disabled');
    $this->drupalPostForm(NULL, [
      'enabled[exclude]' => TRUE,
    ], $this->t('Save settings'));
    $this->assertFieldChecked('edit-enabled-exclude', 'Exclude enabled');

    // Check new settings.
    $settings = $this->defaultBundle()->getAssignmentSettings('exclude');
    $this->assertTrue($settings['enabled'], 'Enabled exclude plugin');
    $this->assertFalse(isset($settings['types']['config']['features_bundle']), 'Not excluding features_bundle');
    $this->assertFalse(isset($settings['types']['config']['system_simple']), 'Not excluding system_simple');
    $this->assertFalse(isset($settings['types']['config']['user_role']), 'Not excluding user_role');
    $this->assertFalse($settings['curated'], 'Not excluding curated items');
    $this->assertFalse($settings['module']['namespace'], 'Not excluding by namespace');

    // Can we run assignment with no settings?
    $this->drupalGet('admin/config/development/features');

    // Can we configure the method?
    $this->drupalPostForm('admin/config/development/features/bundle/_exclude/default', [
      'types[config][system_simple]' => TRUE,
      'types[config][user_role]' => FALSE,
      'curated' => TRUE,
      'module[namespace]' => FALSE,
    ], $this->t('Save settings'));

    // Check form results.
    $this->drupalGet('admin/config/development/features/bundle/_exclude/default');
    $this->assertNoFieldChecked('edit-types-config-features-bundle', 'Saved, features_bundle is not checked');
    $this->assertFieldChecked('edit-types-config-system-simple', 'Saved, system_simple is checked');
    $this->assertNoFieldChecked('edit-types-config-user-role', 'Saved, user_role is not checked');
    $this->assertFieldChecked('edit-curated', 'Saved, curated is checked');
    $this->assertNoFieldChecked('edit-module-namespace', 'Saved, namespace is not checked');

    // Check final values.
    $settings = $this->defaultBundle()->getAssignmentSettings('exclude');
    $this->assertFalse(isset($settings['types']['config']['features_bundle']), 'Saved, not excluding features_bundle');
    $this->assertTrue(isset($settings['types']['config']['system_simple']), 'Saved, excluding system_simple');
    $this->assertFalse(isset($settings['types']['config']['user_role']), 'Saved, not excluding user_role');
    $this->assertTrue($settings['curated'], 'Saved, excluding curated items');
    $this->assertFalse($settings['module']['namespace'], 'Saved, not excluding by namespace');
  }

}
