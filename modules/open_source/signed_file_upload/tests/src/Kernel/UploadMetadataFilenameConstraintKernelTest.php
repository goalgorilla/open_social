<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\DataObject\UploadMetadata;
use Drupal\signed_file_upload\Exception\InvalidContentException;

/**
 * Client-suggested filename (Upload-Metadata) vs field constraints.
 *
 * @group signed_file_upload
 */
final class UploadMetadataFilenameConstraintKernelTest extends SignedFileUploadWithEntityDestinationTestBase {

  /**
   * Test that for image fields the upload filename extensions are validated.
   *
   * Default setup from SignedUploadEntityDestinationTrait uses an image field.
   *
   * @param non-empty-string $filename
   *   The filename to test.
   * @param bool $expectSuccess
   *   Whether this filename should pass or fail.
   *
   * @dataProvider provideSuggestedFilenamesForImageField
   */
  public function testBeginUploadSessionValidatesSuggestedFilenameAgainstFieldExtensions(
    string $filename,
    bool $expectSuccess,
  ): void {
    $destination = $this->entityDestination();

    if (!$expectSuccess) {
      $this->expectException(InvalidContentException::class);
    }

    $grant = $this->manager()->beginUploadSession(
      $destination,
      $this->account(),
      new UploadMetadata(filename: $filename),
    );

    $this->assertSame($filename, $grant->session->metadata->filename);
  }

  /**
   * Expectations assume: allowed extensions come from the image field.
   *
   * Validation uses the suffix after the last "." (case-insensitive).
   */
  public function provideSuggestedFilenamesForImageField(): \Generator {
    yield 'allowed jpg' => ['vacation.jpg', TRUE];
    yield 'allowed uppercase extension' => ['photo.JPEG', TRUE];
    yield 'disallowed pdf on image field' => ['document.pdf', FALSE];
    yield 'no extension' => ['README', FALSE];
    yield 'last extension wins' => ['trick.jpg.exe', FALSE];
    yield 'path segments in name rejected or normalized' => ['sub/dir/cat.png', FALSE];
  }

  /**
   * PDF only field must accept a PDF file.
   */
  public function testPdfOnlyFieldAcceptsPdfAndRejectsPng(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_pdf',
      'entity_type' => 'node',
      'type' => 'file',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_pdf',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'PDF only',
      'settings' => ['file_extensions' => 'pdf'],
    ])->save();

    $grant = $this->manager()->beginUploadSession(
      new EntityFieldUploadDestination('node', 'article', 'field_pdf'),
      $this->account(),
      new UploadMetadata(filename: 'x.pdf'),
    );
    $this->assertSame('x.pdf', $grant->session->metadata->filename);

  }

  /**
   * PDF field must reject a non .pdf file.
   */
  public function testPdfOnlyFieldRejectsPng(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_pdf',
      'entity_type' => 'node',
      'type' => 'file',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_pdf',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'PDF only',
      'settings' => ['file_extensions' => 'pdf'],
    ])->save();

    $this->expectException(InvalidContentException::class);
    $this->manager()->beginUploadSession(
      new EntityFieldUploadDestination('node', 'article', 'field_pdf'),
      $this->account(),
      new UploadMetadata(filename: 'x.png'),
    );
  }

  /**
   * Filenames longer than Drupal's FileNameLength default (240) are rejected.
   */
  public function testBeginUploadSessionRejectsFilenameLongerThanFileNameLengthLimit(): void {
    $longBasename = str_repeat('a', 237) . '.jpg';
    $this->assertSame(241, strlen($longBasename));

    $this->expectException(InvalidContentException::class);
    $this->manager()->beginUploadSession(
      $this->entityDestination(),
      $this->account(),
      new UploadMetadata(filename: $longBasename),
    );
  }

  /**
   * Insecure extensions in the filename are rejected.
   *
   * Corresponds to the FileExtensionSecure constraint.
   */
  public function testBeginUploadSessionRejectsInsecureExtension(): void {
    $this->installConfig(['system']);
    $this->config('system.file')->set('allow_insecure_uploads', FALSE)->save();

    $this->expectException(InvalidContentException::class);
    $this->manager()->beginUploadSession(
      $this->entityDestination(),
      $this->account(),
      new UploadMetadata(filename: 'photo.php'),
    );
  }

  /**
   * Insecure extensions in the filename are rejected.
   *
   * Corresponds to the FileExtensionSecure constraint.
   *
   * The basename ends in .jpg but still contains a .php. segment per Drupal's
   * insecure extension regex when allow_insecure_uploads is FALSE.
   */
  public function testBeginUploadSessionRejectsInsecureExtensionEmbeddedInFilename(): void {
    $this->installConfig(['system']);
    $this->config('system.file')->set('allow_insecure_uploads', FALSE)->save();

    $this->expectException(InvalidContentException::class);
    $this->manager()->beginUploadSession(
      $this->entityDestination(),
      $this->account(),
      new UploadMetadata(filename: 'photo.php.jpg'),
    );
  }

  /**
   * Insecure extension segment is rejected when followed by another extension.
   */
  public function testBeginUploadSessionRejectsInsecurePharSegmentBeforeAllowedExtension(): void {
    $this->installConfig(['system']);
    $this->config('system.file')->set('allow_insecure_uploads', FALSE)->save();

    $this->expectException(InvalidContentException::class);
    $this->manager()->beginUploadSession(
      $this->entityDestination(),
      $this->account(),
      new UploadMetadata(filename: 'archive.phar.jpg'),
    );
  }

}
