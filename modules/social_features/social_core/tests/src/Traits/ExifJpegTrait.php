<?php

namespace Drupal\Tests\social_core\Traits;

use Drupal\file\Entity\File;

/**
 * Creates JPEG files carrying an EXIF Orientation tag.
 */
trait ExifJpegTrait {

  /**
   * Saves a permanent file entity with the given contents.
   *
   * @param string $name
   *   The file name.
   * @param string $data
   *   The file contents.
   *
   * @return \Drupal\file\Entity\File
   *   The saved file.
   */
  protected function createFile(string $name, string $data): File {
    $uri = 'public://' . $name;
    file_put_contents($uri, $data);
    $file = File::create(['uri' => $uri, 'status' => 1]);
    $file->save();

    return $file;
  }

  /**
   * Builds a 40x30 JPEG, with an EXIF Orientation tag when one is given.
   *
   * @param int|null $orientation
   *   The EXIF Orientation tag, or NULL to omit the EXIF segment.
   *
   * @return string
   *   The JPEG bytes.
   */
  protected function createJpeg(?int $orientation): string {
    ob_start();
    imagejpeg(imagecreatetruecolor(40, 30));
    $jpeg = (string) ob_get_clean();
    if ($orientation === NULL) {
      return $jpeg;
    }

    // A big-endian TIFF header with one IFD0 entry: Orientation (0x0112).
    $exif = "Exif\x00\x00MM\x00\x2A\x00\x00\x00\x08\x00\x01"
      . "\x01\x12\x00\x03\x00\x00\x00\x01" . pack('n', $orientation) . "\x00\x00"
      . "\x00\x00\x00\x00";

    // Insert the APP1 segment right after the SOI marker.
    return substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif . substr($jpeg, 2);
  }

}
