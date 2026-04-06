<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\UploadConstraintResolver;
use Drupal\signed_file_upload\UploadConstraintResolverInterface;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Test the upload constraint resolver for entity destinations.
 *
 * @group signed_file_upload
 */
class UploadConstraintResolverEntityDestinationTest extends SignedFileUploadTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'field',
    'text',
    'image',
    'node',
  ];

  /**
   * The constraint resolver under test.
   */
  private UploadConstraintResolver $constraintResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('node');

    $this->createContentType([
      'type' => 'article',
    ]);

    $this->constraintResolver = $this->container->get(UploadConstraintResolverInterface::class);
  }

  /**
   * Test that the constraint resolver properly checks field filesize limits.
   *
   * @dataProvider provideFilesizeConstraints
   */
  public function testProperlyHandlesMaxFilesize(string $max_filesize, int $expected_max_bytes): void {
    $this->createField(
      'node',
      'article',
      'field_file',
      'file',
      ['max_filesize' => $max_filesize],
    );

    $destination = new EntityFieldUploadDestination('node', 'article', 'field_file');
    $constraints = $this->constraintResolver->resolve($destination);

    $this->assertEquals($expected_max_bytes, $constraints->maxBytes);
  }

  /**
   * File size test cases for the field constraint resolver.
   */
  public function provideFilesizeConstraints() : \Generator {
    yield '1 kB' => ['1 kB', 1024];

    yield '2 MB' => ['2 MB', 2097152];

    yield '1.5 GB' => ['1.5 GB', 1610612736];

    yield '1.5 Gb is interpreted by Drupal as 1 GB' => ['1.5 Gb', 1610612736];
  }

  /**
   * Test that the constraint resolver properly checks field allowed extensions.
   *
   * @dataProvider provideExtensionConstraints
   */
  public function testProperlyHandlesFileExtensions(?string $file_extensions, array $expected_extensions): void {
    $this->createField(
      'node',
      'article',
      'field_file',
      'file',
      $file_extensions ? ['file_extensions' => $file_extensions] : [],
    );

    $destination = new EntityFieldUploadDestination('node', 'article', 'field_file');
    $constraints = $this->constraintResolver->resolve($destination);

    $this->assertEquals($expected_extensions, $constraints->allowedExtensions);
  }

  /**
   * File size test cases for the field constraint resolver.
   */
  public function provideExtensionConstraints() : \Generator {
    yield 'defaults to txt' => [NULL, ['txt']];

    yield "parses 'png gif jpg jpeg webp'" => ['png gif jpg jpeg webp', ['png', 'gif', 'jpg', 'jpeg', 'webp']];

    yield "parses 'pdf'" => ['pdf', ['pdf']];

    yield "parses 'mp3    mp4'" => ['mp3    mp4', ['mp3', 'mp4']];
  }

  /**
   * Test that file fields have no image constraints.
   */
  public function testNoImageDimensionConstraintsForFile(): void {
    $this->createField(
      'node',
      'article',
      'field_file',
      'file',
    );

    $destination = new EntityFieldUploadDestination('node', 'article', 'field_file');
    $constraints = $this->constraintResolver->resolve($destination);

    $this->assertNull($constraints->imageDimensionBounds);
  }

  /**
   * Test that image fields have no image constraints by default.
   */
  public function testNoImageDimensionConstraintsForImageByDefault(): void {
    $this->createField(
      'node',
      'article',
      'field_file',
      'image',
    );

    $destination = new EntityFieldUploadDestination('node', 'article', 'field_file');
    $constraints = $this->constraintResolver->resolve($destination);

    $this->assertNull($constraints->imageDimensionBounds);
  }

  /**
   * Test that image fields can have maximum dimensions.
   */
  public function testMaxImageDimensionConstraintsForImage(): void {
    $this->createField(
      'node',
      'article',
      'field_file',
      'image',
      ['max_resolution' => '1024x1024']
    );

    $destination = new EntityFieldUploadDestination('node', 'article', 'field_file');
    $constraints = $this->constraintResolver->resolve($destination);

    $this->assertEquals($constraints->imageDimensionBounds?->max?->width, 1024);
    $this->assertEquals($constraints->imageDimensionBounds?->max?->height, 1024);
    $this->assertEquals($constraints->imageDimensionBounds?->min, NULL);
  }

  /**
   * Test that image fields can have minimum dimensions.
   */
  public function testMinimumImageDimensionConstraintsForImage(): void {
    $this->createField(
      'node',
      'article',
      'field_file',
      'image',
      ['min_resolution' => '1024x1024']
    );

    $destination = new EntityFieldUploadDestination('node', 'article', 'field_file');
    $constraints = $this->constraintResolver->resolve($destination);

    $this->assertEquals($constraints->imageDimensionBounds?->max, NULL);
    $this->assertEquals($constraints->imageDimensionBounds?->min?->width, 1024);
    $this->assertEquals($constraints->imageDimensionBounds?->min?->height, 1024);
  }

  /**
   * Test that image fields can have both dimensions constrainted.
   */
  public function testImageDimensionConstraintsForImage(): void {
    $this->createField(
      'node',
      'article',
      'field_file',
      'image',
      [
        'max_resolution' => '4096x2048',
        'min_resolution' => '1024x1024',
      ]
    );

    $destination = new EntityFieldUploadDestination('node', 'article', 'field_file');
    $constraints = $this->constraintResolver->resolve($destination);

    $this->assertEquals($constraints->imageDimensionBounds?->max?->width, 4096);
    $this->assertEquals($constraints->imageDimensionBounds?->max?->height, 2048);
    $this->assertEquals($constraints->imageDimensionBounds?->min?->width, 1024);
    $this->assertEquals($constraints->imageDimensionBounds?->min?->height, 1024);
  }

  /**
   * Create a field on an entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param string $field_name
   *   The field machine name.
   * @param string $type
   *   The field type.
   * @param array $settings
   *   The field settings, if any.
   * @param int $cardinality
   *   The field cardinality (default 1).
   * @param bool $required
   *   Whether the field is required (default FALSE).
   * @param string $label
   *   The field label.
   */
  protected function createField(string $entity_type, string $bundle, string $field_name, string $type, array $settings = [], int $cardinality = 1, bool $required = FALSE, string $label = "Test Field") : void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $type,
      'cardinality' => $cardinality,
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
      'required' => $required,
      'settings' => $settings,
    ])->save();
  }

}
