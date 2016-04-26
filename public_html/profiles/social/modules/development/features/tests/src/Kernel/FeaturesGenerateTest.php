<?php

namespace Drupal\Tests\features\Kernel;

use Drupal\features\Entity\FeaturesBundle;
use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;

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
    $package->appendConfig('system.site');
    $this->featuresManager->setPackage($package);
  }

  /**
   * @covers \Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationArchive
   */
  public function testExportArchive() {
    $filename = file_directory_temp() . '/' . self::PACKAGE_NAME . '.tar.gz';
    if (file_exists($filename)) {
      unlink($filename);
    }
    $this->assertFalse(file_exists($filename), 'Archive file already exists.');

    $this->generator->generatePackages('archive', $this->assigner->getBundle(), [self::PACKAGE_NAME]);
    $this->assertTrue(file_exists($filename), 'Archive file was not generated.');
  }

  public function testGeneratorWithBundle() {

    $filename = file_directory_temp() . '/giraffe_' . self::PACKAGE_NAME . '.tar.gz';
    if (file_exists($filename)) {
      unlink($filename);
    }
    $this->assertFalse(file_exists($filename), 'Archive file already exists.');

    $bundle = FeaturesBundle::create([
      'machine_name' => 'giraffe'
    ]);

    $this->generator->generatePackages('archive', $bundle, [self::PACKAGE_NAME]);

    $package = $this->featuresManager->getPackage(self::PACKAGE_NAME);
    $this->assertNull($package);

    $package = $this->featuresManager->getPackage('giraffe_' . self::PACKAGE_NAME);
    $this->assertEquals('giraffe_' . self::PACKAGE_NAME, $package->getMachineName());
    $this->assertEquals('giraffe', $package->getBundle());

    $this->assertTrue(file_exists($filename), 'Archive file was not generated.');
  }

  /**
   * @covers \Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationWrite
   */
  public function testExportWrite() {
    // Set a fake drupal root, so the testbot can also write into it.
    vfsStream::setup('drupal');
    \Drupal::getContainer()->set('app.root', 'vfs://drupal');

    $package = $this->featuresManager->getPackage(self::PACKAGE_NAME);
    // Find out where package will be exported
    list($full_name, $path) = $this->featuresManager->getExportInfo($package, $this->assigner->getBundle());
    $path = 'vfs://drupal/' . $path . '/' . $full_name;
    if (file_exists($path)) {
      file_unmanaged_delete_recursive($path);
    }
    $this->assertFalse(file_exists($path), 'Package directory already exists.');

    $this->generator->generatePackages('write', $this->assigner->getBundle(), [self::PACKAGE_NAME]);
    $this->assertTrue(file_exists($path), 'Package directory was not generated.');
    $this->assertTrue(file_exists($path . '/' . self::PACKAGE_NAME . '.info.yml'), 'Package info.yml not generated.');
    $this->assertTrue(file_exists($path . '/config/install'), 'Package config/install not generated.');
    $this->assertTrue(file_exists($path . '/config/install/system.site.yml'), 'Config.yml not exported.');
  }
}
