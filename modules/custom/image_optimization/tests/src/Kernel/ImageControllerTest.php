<?php

namespace Drupal\Tests\image_optimization\Kernel;

use Drupal\file\Entity\File;
use Drupal\image_optimization\ImageUrlGenerator;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the image controller.
 *
 * @coversDefaultClass \Drupal\image_optimization\Controller\ImageController
 * @group image_optimization
 */
class ImageControllerTest extends KernelTestBase {

  use KeysTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'file',
    'image',
    'image_optimization',
  ];

  /**
   * The file under test.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The image url generator service.
   *
   * @var \Drupal\image_optimization\ImageUrlGeneratorInterface
   */
  protected $imageUrlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installConfig(['image', 'image_optimization']);

    $this->fileSystem = $this->container->get('file_system');
    $destination = 'public://image_optimization-test.png';
    $this->fileSystem->copy($this->root . '/core/misc/druplicon.png', $destination);

    $this->file = File::create([
      'uri' => $destination,
    ]);
    $this->file->save();

    $this->setUpKeys();

    $this->imageFactory = $this->container->get('image.factory');
    $this->streamWrapperManager = $this->container->get('stream_wrapper_manager');
    $this->imageUrlGenerator = $this->container->get('image_optimization.image_url_generator');
  }

  /**
   * Test transform image controller.
   */
  public function testImageController(): void {
    $image = $this->imageFactory->get($this->file->getFileUri());
    $uri = $this->imageUrlGenerator->generate(
      $this->file->uuid(),
      $image,
      50
    );

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = $this->container->get('http_kernel');
    $request = Request::create($uri);

    $response = $http_kernel->handle($request);
    $this->assertInstanceOf(BinaryFileResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $image_data = getimagesize($response->getFile());
    $this->assertIsArray($image_data);
    $this->assertEquals(44, $image_data[0]);
  }

  /**
   * Test existing transformed image.
   */
  public function testExistingImage(): void {
    $image = $this->imageFactory->get($this->file->getFileUri());
    $uri = $this->imageUrlGenerator->generate(
      $this->file->uuid(),
      $image,
      50
    );

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = $this->container->get('http_kernel');
    $request = Request::create($uri);

    $first_response = $http_kernel->handle($request);
    $this->assertEquals(200, $first_response->getStatusCode());
    $first_file = $first_response->getFile();
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\File\File', $first_file);

    $uri = $this->imageUrlGenerator->generate(
      $this->file->uuid(),
      $image,
      50
    );
    $request = Request::create($uri);
    $second_response = $http_kernel->handle($request);
    $this->assertEquals(200, $second_response->getStatusCode());
    $second_file = $second_response->getFile();
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\File\File', $second_file);

    $this->assertEquals($first_file->getPathname(), $second_file->getPathname());
  }

}
