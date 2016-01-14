<?php

namespace Drupal\Tests\features\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group features
 */
class FeaturesGenerateTest extends KernelTestBase {

  const PACKAGE_NAME = 'my_test_package';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['features', 'system'];

  /**
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * @var \Drupal\features\FeaturesGeneratorInterface
   */
  protected $generator;

  protected $strictConfigSchema = FALSE;

  /**
   * @var \Drupal\features\FeaturesAssignerInterface
   */
  protected $assigner;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('features');
    $this->installConfig('system');
    \Drupal::configFactory()->getEditable('features.settings')
      ->set('assignment.enabled', [])
      ->set('bundle.settings', [])
      ->save();

    $this->featuresManager = \Drupal::service('features.manager');
    $this->generator = \Drupal::service('features_generator');
    $this->assigner = \Drupal::service('features_assigner');

    $this->featuresManager->initPackage(self::PACKAGE_NAME, 'My test package');
    $package = $this->featuresManager->getPackage(self::PACKAGE_NAME);
    $package['config'][] = 'system.site';
    $this->featuresManager->setPackage($package);
  }


  public function testExportArchive() {
    $filename = file_directory_temp() . '/' . self::PACKAGE_NAME . '.tar.gz';
    if (file_exists($filename)) {
      unlink($filename);
    }
    $this->assertFalse(file_exists($filename), 'Archive file already exists.');

    $this->generator->generatePackages('archive', [self::PACKAGE_NAME], $this->assigner->getBundle());
    $this->assertTrue(file_exists($filename), 'Archive file was not generated.');
  }

  public function testExportWrite() {
    $package = $this->featuresManager->getPackage(self::PACKAGE_NAME);
    // Find out where package will be exported
    list($full_name, $path) = $this->featuresManager->getExportInfo($package, $this->assigner->getBundle());
    $path = $path . '/' . $full_name;
    if (file_exists($path)) {
      file_unmanaged_delete_recursive($path);
    }
    $this->assertFalse(file_exists($path), 'Package directory already exists.');

    $this->generator->generatePackages('write', [self::PACKAGE_NAME], $this->assigner->getBundle());
    $this->assertTrue(file_exists($path), 'Package directory was not generated.');
    $this->assertTrue(file_exists($path . '/' . self::PACKAGE_NAME . '.info.yml'), 'Package info.yml not generated.');
    $this->assertTrue(file_exists($path . '/config/install'), 'Package config/install not generated.');
    $this->assertTrue(file_exists($path . '/config/install/system.site.yml'), 'Config.yml not exported.');
  }
}
