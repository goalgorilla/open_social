<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\DataObject\UploadMetadata;
use Drupal\signed_file_upload\DataObject\UploadSessionGrant;
use Drupal\signed_file_upload\UploadSessionManagerInterface;
use Drupal\Tests\signed_file_upload\Traits\SignedUploadEntityDestinationTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared bootstrap and HTTP helpers for signed_file_upload kernel tests.
 */
abstract class SignedFileUploadWithEntityDestinationTestBase extends SignedFileUploadTestBase {

  use SignedUploadEntityDestinationTrait;

  /**
   * Minimal valid JPEG (1×1), produced with GD `imagejpeg()` for kernel tests.
   *
   * Used for happy-path finalize and any flow that runs full image validation.
   */
  protected const MINIMAL_VALID_JPEG_B64 = '/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AAEQgAAQABAwERAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A/KqgD//Z';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'field',
    'text',
    'file',
    'image',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('node');

    $this->setUpDefaultDestination();
  }

  /**
   * Get the upload session manager.
   *
   * @return \Drupal\signed_file_upload\UploadSessionManagerInterface
   *   The upload session manager.
   */
  protected function manager(): UploadSessionManagerInterface {
    return $this->container->get(UploadSessionManagerInterface::class);
  }

  /**
   * Begin a session with a known Upload-Length.
   *
   * @param non-negative-int $length
   *   The Upload-Length.
   *
   * @return \Drupal\signed_file_upload\DataObject\UploadSessionGrant
   *   A grant for an upload session.
   */
  protected function beginSessionKnownLength(int $length): UploadSessionGrant {
    return $this->manager()->beginUploadSession(
      $this->entityDestination(),
      $this->account(),
      new UploadMetadata(filename: 'test.jpg'),
      $length,
    );
  }

  /**
   * Begin session with a deferred length.
   *
   * @return \Drupal\signed_file_upload\DataObject\UploadSessionGrant
   *   An upload session grant without an Upload-Length set.
   */
  protected function beginSessionDeferLength(): UploadSessionGrant {
    return $this->manager()->beginUploadSession(
      $this->entityDestination(),
      $this->account(),
      new UploadMetadata(filename: 'test.jpg'),
      NULL,
    );
  }

  /**
   * Starts a session for an arbitrary image field destination.
   *
   * @param \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination $destination
   *   The entity destination.
   * @param non-empty-string $filename
   *   The filename.
   * @param non-negative-int|null $length
   *   The Upload-Length.
   *
   * @return \Drupal\signed_file_upload\DataObject\UploadSessionGrant
   *   A grant for an upload session.
   */
  protected function beginSessionForDestination(
    EntityFieldUploadDestination $destination,
    string $filename,
    ?int $length,
  ): UploadSessionGrant {
    return $this->manager()->beginUploadSession(
      $destination,
      $this->account(),
      new UploadMetadata(filename: $filename),
      $length,
    );
  }

  /**
   * Creates an image field on article and returns its upload destination.
   *
   * @param non-empty-string $fieldName
   *   The field name to use.
   * @param array<string, mixed> $settings
   *   Field settings (e.g. max_filesize, max_resolution, min_resolution).
   *
   * @return \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination
   *   The upload destination for the created field.
   */
  protected function installImageField(string $fieldName, array $settings = []): EntityFieldUploadDestination {
    FieldStorageConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'node',
      'type' => 'image',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => $fieldName,
      'settings' => $settings,
    ])->save();

    return new EntityFieldUploadDestination('node', 'article', $fieldName);
  }

  /**
   * Creates a file field on article and returns its upload destination.
   *
   * Use for tests that only need generic file constraints (e.g. max_filesize),
   * not image rules.
   *
   * @param non-empty-string $fieldName
   *   The field name to use.
   * @param array<string, mixed> $settings
   *   Field settings (e.g. max_filesize, file_extensions). Defaults include
   *   `txt` only.
   *
   * @return \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination
   *   The upload destination for the field.
   */
  protected function installFileField(string $fieldName, array $settings = []): EntityFieldUploadDestination {
    FieldStorageConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'node',
      'type' => 'file',
      'cardinality' => 1,
    ])->save();

    $defaults = [
      'file_extensions' => 'txt',
    ];

    FieldConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => $fieldName,
      'settings' => array_merge($defaults, $settings),
    ])->save();

    return new EntityFieldUploadDestination('node', 'article', $fieldName);
  }

  /**
   * Asserts the tus response carries Tus-Max-Size equal to expected byte cap.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response received from a request.
   * @param int $expectedBytes
   *   The expected Tus-Max-Size header value.
   */
  protected function assertTusMaxSize(Response $response, int $expectedBytes): void {
    $this->assertSame((string) $expectedBytes, $response->headers->get('Tus-Max-Size'));
  }

  /**
   * Builds a payload with JPEG SOI so PATCH requests satisfy magic-byte checks.
   *
   * @param int $length
   *   The length of the payload.
   *
   * @return string
   *   A payload which starts with the JPEG special bytes.
   */
  protected function jpegLikePayload(int $length): string {
    if ($length <= 0) {
      return '';
    }
    $prefix = "\xFF\xD8\xFF";
    if ($length <= strlen($prefix)) {
      return substr($prefix, 0, $length);
    }
    return $prefix . str_repeat('x', $length - strlen($prefix));
  }

  /**
   * Valid minimal JPEG bytes (decodes {@link self::MINIMAL_VALID_JPEG_B64}).
   *
   * @return non-empty-string
   *   A minimal valid JPEG file in binary.
   */
  protected function minimalValidJpegBytes(): string {
    $binary = base64_decode(static::MINIMAL_VALID_JPEG_B64, TRUE);
    $this->assertNotFalse($binary);
    assert($binary !== '');
    return $binary;
  }

}
