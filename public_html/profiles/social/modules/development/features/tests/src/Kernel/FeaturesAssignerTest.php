<?php

namespace Drupal\Tests\features\Kernel;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\features\FeaturesBundleInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group features
 */
class FeaturesAssignerTest extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'config'];

  protected $strictConfigSchema = FALSE;

  /**
   * Test bundle auto-creation during config import.
   *
   * We check the case where the import also causes features to be installed,
   * so at the time auto-creation happens there's not yet a default bundle.
   */
  public function testBundleAutoCreationImport() {
    // Install the feature.
    $installer = $this->container->get('module_installer');
    // Have to do these separately so features_modules_installed() doesn't
    // just exit.
    $installer->install(['features']);
    $installer->install(['test_feature']);

    // Save config.
    $this->copyConfig(
      $this->container->get('config.storage'),
      $this->container->get('config.storage.sync')
    );

    // Uninstall modules.
    $installer->uninstall(['features', 'test_feature']);

    // Restore the config from after install..
    $this->configImporter()->import();

    // Find the auto-created bundle.
    $bundle_storage = $this->container->get('entity_type.manager')
      ->getStorage('features_bundle');
    $bundle = $bundle_storage->load('test');
    $this->assertNotNull($bundle, "Features bundle doesn't exist");
    $this->assertContains(
      'Auto-generated bundle',
      $bundle->getDescription(),
      "Features bundle not auto-created");
  }

}
