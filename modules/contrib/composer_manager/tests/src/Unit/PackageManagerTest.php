<?php

/**
 * @file
 * Contains \Drupal\Tests\composer_manager\Unit\PackageManagerTest.
 */

namespace Drupal\Tests\composer_manager\Unit;

use Drupal\composer_manager\PackageManager;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\composer_manager\PackageManager
 * @group composer_manager
 */
class PackageManagerTest extends UnitTestCase {

  /**
   * @var \Drupal\composer_manager\PackageManager
   */
  protected $manager;

  /**
   * Package fixtures.
   *
   * @var array
   */
  protected $packages = [
    'core' => [
      'name' => 'drupal/core',
      'type' => 'drupal-core',
    ],
    'extension' => [
      'commerce_kickstart' => [
        'name' => 'drupal/commerce_kickstart',
        'require' => [
          'symfony/css-selector' => '2.6.*',
        ],
        'extra' => [
          'path' => 'profiles/commerce_kickstart/composer.json',
        ],
      ],
      'test1' => [
        'name' => 'drupal/test1',
        'require' => [
          'symfony/intl' => '2.6.*',
        ],
        'extra' => [
          'path' => 'modules/test1/composer.json',
        ],
      ],
      'test2' => [
        'name' => 'drupal/test2',
        'require' => [
          'symfony/config' => '2.6.*',
        ],
        'extra' => [
          'path' => 'sites/all/modules/test2/composer.json',
        ],
      ],
    ],
    'installed' => [
      [
        'name' => 'symfony/dependency-injection',
        'version' => 'v2.6.3',
        'description' => 'Symfony DependencyInjection Component',
        'homepage' => 'http://symfony.com',
      ],
      [
        'name' => 'symfony/event-dispatcher',
        'version' => 'v2.6.3',
        'description' => 'Symfony EventDispatcher Component',
        'homepage' => 'http://symfony.com',
        'require' => [
          // symfony/event-dispatcher doesn't really have this requirement,
          // we're lying for test purposes.
          'symfony/yaml' => 'dev-master',
        ],
      ],
      [
        'name' => 'symfony/yaml',
        'version' => 'dev-master',
        'source' => [
          'type' => 'git',
          'url' => 'https://github.com/symfony/Yaml.git',
          'reference' => '3346fc090a3eb6b53d408db2903b241af51dcb20',
        ],
        // description and homepage intentionally left out to make sure
        // getRequiredPackages(] can cope with that.
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $structure = [
      'vendor' => [
        'composer' => [
          'installed.json' => json_encode($this->packages['installed']),
        ],
      ],
      'core' => [
        'composer.json' => json_encode($this->packages['core']),
      ],
      'profiles' => [
        'commerce_kickstart' => [
          'commerce_kickstart.info.yml' => 'type: profile',
          'commerce_kickstart.profile' => '<?php',
          'composer.json' => json_encode($this->packages['extension']['commerce_kickstart']),
        ],
      ],
      'modules' => [
        'test1' => [
          'composer.json' => json_encode($this->packages['extension']['test1']),
          'test1.module' => '<?php',
          'test1.info.yml' => 'type: module',
        ],
      ],
      'sites' => [
        'all' => [
          'modules' => [
            'test2' => [
              'composer.json' => json_encode($this->packages['extension']['test2']),
              'test2.module' => '<?php',
              'test2.info.yml' => 'type: module',
            ],
          ],
        ],
      ],
    ];
    vfsStream::setup('drupal', null, $structure);

    $this->manager = new PackageManager('vfs://drupal');

  }

  /**
   * @covers ::getCorePackage
   */
  public function testCorePackage() {
    $core_package = $this->manager->getCorePackage();
    $this->assertEquals($this->packages['core'], $core_package);
  }

  /**
   * @covers ::getExtensionPackages
   */
  public function testExtensionPackages() {
    $extension_packages = $this->manager->getExtensionPackages();
    $this->assertEquals($this->packages['extension'], $extension_packages);
  }

  /**
   * @covers ::getInstalledPackages
   */
  public function testInstalledPackages() {
    $installed_packages = $this->manager->getInstalledPackages();
    $this->assertEquals($this->packages['installed'], $installed_packages);
  }

  /**
   * @covers ::getRequiredPackages
   * @covers ::processRequiredPackages
   */
  public function testRequiredPackages() {
    $expected_packages = [
      'symfony/css-selector' => [
        'constraint' => '2.6.*',
        'description' => '',
        'homepage' => '',
        'require' => [],
        'required_by' => ['drupal/commerce_kickstart'],
        'version' => '',
      ],
      'symfony/config' => [
        'constraint' => '2.6.*',
        'description' => '',
        'homepage' => '',
        'require' => [],
        'required_by' => ['drupal/test2'],
        'version' => '',
      ],
      'symfony/intl' => [
        'constraint' => '2.6.*',
        'description' => '',
        'homepage' => '',
        'require' => [],
        'required_by' => ['drupal/test1'],
        'version' => '',
      ],
    ];

    $required_packages = $this->manager->getRequiredPackages();
    $this->assertEquals($expected_packages, $required_packages);
  }

  /**
   * @covers ::needsComposerUpdate
   */
  public function testNeedsComposerUpdate() {
    $needs_update = $this->manager->needsComposerUpdate();
    $this->assertEquals(TRUE, $needs_update);
  }

}
