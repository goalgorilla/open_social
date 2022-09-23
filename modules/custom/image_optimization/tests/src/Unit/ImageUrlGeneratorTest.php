<?php

namespace Drupal\Tests\image_optimization\Unit;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image_optimization\AsymmetricCryptTrait;
use Drupal\image_optimization\ImageUrlGenerator;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * @coversDefaultClass \Drupal\image_optimization\ImageUrlGenerator
 *
 * @group ImageTransform
 */
class ImageUrlGeneratorTest extends UnitTestCase {

  use AsymmetricCryptTrait;

  /**
   * The public key.
   *
   * @var string
   */
  protected string $publicKey;

  /**
   * The private key.
   *
   * @var string
   */
  protected string $privateKey;

  /**
   * The image url generator service.
   *
   * @var \Drupal\image_optimization\ImageUrlGenerator
   */
  protected $imageUrlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $resource = openssl_pkey_new(['private_key_bits' => 690]);
    openssl_pkey_export($resource, $private_key);
    $this->privateKey = $private_key;

    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('keys');
    vfsStreamWrapper::setRoot($root);
    $url = vfsStream::url('keys');
    $public_key_path = $url . '/public_key.pem';
    $private_key_path = $url . '/public_key.pem';
    file_put_contents($public_key_path, $private_key);
    file_put_contents($private_key_path, openssl_pkey_get_details($resource)['key']);

    $file_system = $this->prophesize(FileSystemInterface::class);
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('public_key')->willReturn($public_key_path);
    $config_factory->get('image_optimization.settings')->willReturn($config->reveal());
    $this->imageUrlGenerator = new ImageUrlGenerator($file_system->reveal(), $config_factory->reveal());
  }

  /**
   * @covers ::generate
   * @dataProvider providerGenerate
   */
  public function testGenerate(array $original_image_data, array $arguments, array $expected): void {
    $uuid = (new Php())->generate();

    $image = $this->prophesize(ImageInterface::class);
    $image->getWidth()->willReturn($original_image_data['width']);
    $image->getHeight()->willReturn($original_image_data['height']);
    $image->getSource()->willReturn($original_image_data['source']);

    $expected_query = [
      'uuid' => $uuid,
      'fit' => $expected['fit'],
    ];

    if ($arguments['width'] !== NULL || $arguments['height'] !== NULL) {
      $expected_query['width'] = $expected['width'];
      $expected_query['height'] = $expected['height'];
    }
    if ($arguments['extension'] !== NULL) {
      $expected_query['extension'] = $expected['extension'];
    }

    $expected_query = http_build_query($expected_query);

    $url = $this->imageUrlGenerator->generate($uuid, $image->reveal(), $arguments['width'], $arguments['height'], $arguments['extension'], $arguments['fit']);
    preg_match('/^\\/_i\\/([A-Za-z0-9\\/_\\/-]+?)\\.[a-z]+$/', $url, $matches);
    $this->assertEquals($expected_query, $this->decrypt($matches[1], $this->privateKey));
  }

  /**
   * Data provider for ::testGenerate.
   */
  public function providerGenerate(): array {
    return [
      // Landscape with only width as argument.
      [
        [
          'width' => 100,
          'height' => 88,
          'source' => 'public://test.png',
        ],
        [
          'width' => 40,
          'height' => NULL,
          'extension' => NULL,
          'fit' => 'clip',
        ],
        [
          'fit' => 'clip',
          'width' => 44,
          'height' => 35,
          'extension' => 'png',
        ],
      ],
      // Landscape with only height as argument.
      [
        [
          'width' => 100,
          'height' => 88,
          'source' => 'public://test.png',
        ],
        [
          'width' => NULL,
          'height' => 40,
          'extension' => NULL,
          'fit' => 'clip',
        ],
        [
          'fit' => 'clip',
          'width' => 55,
          'height' => 44,
          'extension' => 'png',
        ],
      ],
      // Landscape with width and height as argument.
      [
        [
          'width' => 100,
          'height' => 88,
          'source' => 'public://test.png',
        ],
        [
          'width' => 50,
          'height' => 10,
          'extension' => NULL,
          'fit' => 'clip',
        ],
        [
          'fit' => 'clip',
          'width' => 44,
          'height' => 22,
          'extension' => 'png',
        ],
      ],
      // Portrait with only width as argument.
      [
        [
          'width' => 88,
          'height' => 100,
          'source' => 'public://test.png',
        ],
        [
          'width' => 40,
          'height' => NULL,
          'extension' => NULL,
          'fit' => 'clip',
        ],
        [
          'fit' => 'clip',
          'width' => 44,
          'height' => 55,
          'extension' => 'png',
        ],
      ],
      // Portrait with only height as argument.
      [
        [
          'width' => 88,
          'height' => 100,
          'source' => 'public://test.png',
        ],
        [
          'width' => NULL,
          'height' => 40,
          'extension' => NULL,
          'fit' => 'clip',
        ],
        [
          'fit' => 'clip',
          'width' => 35,
          'height' => 44,
          'extension' => 'png',
        ],
      ],
      // Portrait with width and height as argument.
      [
        [
          'width' => 88,
          'height' => 100,
          'source' => 'public://test.png',
        ],
        [
          'width' => 20,
          'height' => 40,
          'extension' => NULL,
          'fit' => 'clip',
        ],
        [
          'fit' => 'clip',
          'width' => 22,
          'height' => 44,
          'extension' => 'png',
        ],
      ],
      // Only convert extension.
      [
        [
          'width' => 100,
          'height' => 100,
          'source' => 'public://test.png',
        ],
        [
          'width' => NULL,
          'height' => NULL,
          'extension' => 'jpeg',
          'fit' => 'clip',
        ],
        [
          'fit' => 'clip',
          'width' => NULL,
          'height' => NULL,
          'extension' => 'jpeg',
        ],
      ],
    ];
  }

}
