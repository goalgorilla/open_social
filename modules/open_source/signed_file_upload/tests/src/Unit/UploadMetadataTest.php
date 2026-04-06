<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Unit;

use Drupal\signed_file_upload\DataObject\UploadMetadata;
use Drupal\Tests\UnitTestCase;

/**
 * Test the logic for the UploadMetadata object.
 */
class UploadMetadataTest extends UnitTestCase {

  /**
   * Test that the extension function returns the last extension.
   */
  public function testFileExtensionReturnsLast() : void {
    $metadata = new UploadMetadata(filename: 'test.foo.txt');
    $this->assertSame('txt', $metadata->getFileExtension());
  }

  /**
   * Test that it properly encodes to base64 in the header.
   */
  public function testToHeaderBase64Encodes() : void {
    $filename = 'test.txt';
    $metadata = new UploadMetadata(filename: $filename);
    $this->assertSame('filename ' . base64_encode($filename), $metadata->toHeader());
  }

  /**
   * Test that it properly parses a valid header.
   */
  public function testProperlyParsesHeader() : void {
    $filename = 'test.txt';
    $header = 'filename ' . base64_encode($filename);
    $metadata = UploadMetadata::fromHeader($header);
    $this->assertSame($filename, $metadata->filename);
  }

  /**
   * Test that it properly rejects a header missing keys.
   */
  public function testFromHeaderRejectsMissingKeys() : void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Missing metadata keys: filename");
    UploadMetadata::fromHeader("");
  }

  /**
   * Test that it properly rejects a header with extraneous keys.
   */
  public function testFromHeaderRejectsExtraKeys() : void {
    $filename = 'test.txt';
    $header = 'filename ' . base64_encode($filename);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Unsupported metadata keys: foo");
    UploadMetadata::fromHeader("$header,foo");
  }

}
