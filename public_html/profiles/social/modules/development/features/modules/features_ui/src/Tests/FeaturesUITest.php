<?php

/**
 * @file
 * Contains \Drupal\features_ui\Tests\FeaturesUITest.
 */

namespace Drupal\features_ui\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests the creation of a feature.
 *
 * @group features_ui
 */
class FeaturesUITest extends WebTestBase {
  use StringTranslationTrait;

  /**
   * @todo Remove the disabled strict config schema checking.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['features', 'features_ui'];

  /**
   * Tests creating a feature via UI and download it.
   */
  public function testFeaturesUI() {
    $admin_user = $this->drupalCreateUser(['administer site configuration', 'export configuration', 'administer modules']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/development/features');
    // Check the message is displaying if there are no custom bundles.
    $this->assertText($this->t('You have not yet created any bundles. Before generating features, you may wish to create a bundle to group your features within.'));
    // Creating custom bundle.
    $this->drupalGet('admin/config/development/features/bundle');
    $this->drupalPostAjaxForm(NULL, array('bundle[bundle_select]' => 'new'), 'bundle[bundle_select]');
    $edit = [
      'bundle[name]' => 'foo',
      'bundle[machine_name]' => 'foo',
      'bundle[description]' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save settings'));
    $this->drupalGet('admin/config/development/features');
    // Check the message is not displaying if there are custom bundles.
    $this->assertNoText($this->t('You have not yet created any bundles. Before generating features, you may wish to create a bundle to group your features within.'));
  }

}
