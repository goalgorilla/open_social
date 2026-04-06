<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\Core\File\FileExists;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\Enum\TokenType;
use Drupal\signed_file_upload\Enum\UploadState;
use Drupal\signed_file_upload\Exception\InvalidFinalizationDestinationException;
use Drupal\signed_file_upload\Exception\InvalidContentException;
use Drupal\signed_file_upload\Exception\OperationConflictException;
use Drupal\signed_file_upload\Exception\UploadIncompleteException;
use Drupal\signed_file_upload\SignedUploadFileLifecycleInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Finalization promotes staged bytes to a managed file entity.
 *
 * @group signed_file_upload
 */
class FinalizeUploadKernelTest extends SignedFileUploadWithEntityDestinationTestBase {

  /**
   * GD-generated minimal PNG (1×1), base64.
   */
  private const PNG_1X1_B64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADElEQVQImWNgYGAAAAAEAAGjChXjAAAAAElFTkSuQmCC';

  /**
   * GD-generated minimal PNG (11×11), base64.
   */
  private const PNG_11X11_B64 = 'iVBORw0KGgoAAAANSUhEUgAAAAsAAAALCAIAAAAmzuBxAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADUlEQVQYlWNgGAX0BwABdgABDVW5ZgAAAABJRU5ErkJggg==';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
  }

  /**
   * Test that finalization locks the operation.
   */
  public function testFinalizeLocksOperation(): void {
    $jpeg = $this->minimalValidJpegBytes();
    $length = strlen($jpeg);
    $grant = $this->beginSessionKnownLength($length);
    $path = "/api/tus/$grant->uploadToken";

    $patch = $this->tusRequest('PATCH', $path, $jpeg, [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $patch->getStatusCode());
    $this->assertSame((string) $length, $patch->headers->get('Upload-Offset'));

    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);

    $this->simulateLockedFile($grant->session);

    $this->expectException(OperationConflictException::class);
    $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      $this->entityDestination(),
    );
  }

  /**
   * After a complete upload, finalizeUpload returns a temporary file.
   *
   * The file should be in the correct location for the destination. It should
   * be temporary, since making it permanent happens when it's attached to an
   * entity.
   */
  public function testFinalizeUploadReturnsTemporaryFile(): void {
    $jpeg = $this->minimalValidJpegBytes();
    $length = strlen($jpeg);
    $grant = $this->beginSessionKnownLength($length);
    $path = "/api/tus/$grant->uploadToken";

    $patch = $this->tusRequest('PATCH', $path, $jpeg, [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $patch->getStatusCode());
    $this->assertSame((string) $length, $patch->headers->get('Upload-Offset'));

    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);
    $file = $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      $this->entityDestination(),
    );

    $this->assertTrue($file->isTemporary());
    $this->assertSame($length, (int) $file->getSize());
    $this->assertUriFileDoesNotExist($grant->session->artifactUri);
  }

  /**
   * Finalize before all bytes are uploaded throws UploadIncompleteException.
   */
  public function testFinalizeBeforeUploadCompleteThrowsUploadIncomplete(): void {
    $length = strlen($this->minimalValidJpegBytes());
    $grant = $this->beginSessionKnownLength($length);
    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);

    $this->expectException(UploadIncompleteException::class);
    $this->expectExceptionMessageMatches('/expected ' . preg_quote((string) $length, '/') . ' bytes/');
    $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      $this->entityDestination(),
    );
  }

  /**
   * Finalization must use the same destination the session was created with.
   */
  public function testFinalizeWrongDestinationThrowsInvalidFinalizationDestination(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_image2',
      'entity_type' => 'node',
      'type' => 'image',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_image2',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Secondary image',
      'settings' => [],
    ])->save();

    $jpeg = $this->minimalValidJpegBytes();
    $grant = $this->beginSessionKnownLength(strlen($jpeg));
    $path = "/api/tus/$grant->uploadToken";
    $this->tusRequest('PATCH', $path, $jpeg, [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);

    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);

    $this->expectException(InvalidFinalizationDestinationException::class);
    $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      new EntityFieldUploadDestination('node', 'article', 'field_image2'),
    );
  }

  /**
   * Unknown finalization token throws InvalidArgumentException.
   */
  public function testFinalizeWithUnknownFinalizationTokenThrowsInvalidArgument(): void {
    $token = TokenType::Finalization->value . bin2hex(random_bytes(32));
    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid finalization token.');
    $lifecycle->finalizeUpload(
      $token,
      $this->account(),
      $this->entityDestination(),
    );
  }

  /**
   * Finalize rejects content whose detected MIME type does not match the field.
   */
  public function testFinalizeRejectsMismatchingFileContentMime(): void {
    $png = (string) base64_decode(self::PNG_1X1_B64, TRUE);
    $this->assertNotSame('', $png);
    $length = strlen($png);
    $grant = $this->beginSessionKnownLength($length);
    $path = "/api/tus/$grant->uploadToken";
    $this->tusRequest('PATCH', $path, $this->jpegLikePayload($length), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);

    $this->container->get('file_system')->saveData(
      $png,
      $grant->session->artifactUri,
      FileExists::Replace,
    );

    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);

    $this->expectException(InvalidContentException::class);
    $this->expectExceptionMessageMatches('/Detected mime type/');
    $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      $this->entityDestination(),
    );
  }

  /**
   * Direct delete on a finalized session is not allowed.
   */
  public function testDeleteUploadThrowsWhenSessionNotUploading(): void {
    $jpeg = $this->minimalValidJpegBytes();
    $grant = $this->beginSessionKnownLength(strlen($jpeg));
    $path = "/api/tus/$grant->uploadToken";
    $this->tusRequest('PATCH', $path, $jpeg, [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);

    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);
    $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      $this->entityDestination(),
    );

    $session = $this->manager()->loadSession($grant->uploadToken);
    $this->assertNotNull($session);
    $this->assertSame(UploadState::Finalized, $session->state);

    $this->expectException(\InvalidArgumentException::class);
    $lifecycle->deleteUpload($session);
  }

  /**
   * Finalize rejects a staged file larger than the field max_filesize.
   *
   * Corresponds to constraint Drupal FileSizeLimit.
   *
   * This should not happen in a proper implementation but guards against
   * tampering.
   */
  public function testFinalizeRejectsStagedFileExceedingFieldMaxFilesize(): void {
    $destination = $this->installFileField('field_fin_size', ['max_filesize' => '1 kB']);
    $maxBytes = 1024;
    $grant = $this->beginSessionForDestination($destination, 'blob.txt', $maxBytes);
    $path = "/api/tus/$grant->uploadToken";
    $this->tusRequest('PATCH', $path, str_repeat('a', $maxBytes), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);

    $this->container->get('file_system')->saveData(
      str_repeat('B', $maxBytes + 1),
      $grant->session->artifactUri,
      FileExists::Replace,
    );

    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);
    $this->expectException(InvalidContentException::class);
    $this->expectExceptionMessageMatches('/File exceeded maximum filesize/');
    $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      $destination,
    );
  }

  /**
   * Finalize rejects a PNG smaller than the image field min_resolution.
   *
   * Corresponds to FileImageDimensions constraint.
   */
  public function testFinalizeRejectsImageBelowMinResolution(): void {
    $png = base64_decode(self::PNG_1X1_B64, TRUE);
    $this->assertNotFalse($png);
    $length = strlen($png);
    assert($length > 0);
    $destination = $this->installImageField('field_img_min', [
      'min_resolution' => '10x10',
    ]);
    $grant = $this->beginSessionForDestination($destination, 'small.png', $length);
    $path = "/api/tus/$grant->uploadToken";
    $this->tusRequest('PATCH', $path, $png, [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);

    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);
    $this->expectException(InvalidContentException::class);
    $this->expectExceptionMessage('Image dimensions are too small, must be larger than 10x10.');
    $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      $destination,
    );
  }

  /**
   * Finalize rejects a PNG larger than the image field max_resolution.
   *
   * Corresponds to FileImageDimensions constraint.
   */
  public function testFinalizeRejectsImageAboveMaxResolution(): void {
    $png = base64_decode(self::PNG_11X11_B64, TRUE);
    $this->assertNotFalse($png);
    $length = strlen($png);
    assert($length > 0);
    $destination = $this->installImageField('field_img_max', [
      'max_resolution' => '10x10',
    ]);
    $grant = $this->beginSessionForDestination($destination, 'wide.png', $length);
    $path = "/api/tus/$grant->uploadToken";
    $this->tusRequest('PATCH', $path, $png, [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);

    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);
    $this->expectException(InvalidContentException::class);
    $this->expectExceptionMessage('Image dimensions are too large, must be smaller than 10x10.');
    $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      $destination,
    );
  }

  /**
   * Finalize rejects bytes that are not a decodable image (FileIsImage-style).
   */
  public function testFinalizeRejectsCorruptJpegImagePayload(): void {
    $destination = $this->entityDestination();
    $grant = $this->beginSessionForDestination($destination, 'fake.jpg', 30);
    $path = "/api/tus/$grant->uploadToken";
    $this->tusRequest('PATCH', $path, $this->jpegLikePayload(30), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);

    $corruptJpeg = "\xFF\xD8\xFF" . str_repeat("\x00", 27);
    $this->container->get('file_system')->saveData(
      $corruptJpeg,
      $grant->session->artifactUri,
      FileExists::Replace,
    );

    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);
    $this->expectException(InvalidContentException::class);
    $this->expectExceptionMessage('Uploaded image is not a valid image file.');
    $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      $destination,
    );
  }

}
