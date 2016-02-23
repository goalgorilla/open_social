<?php

/**
 * @file
 * Contains \Drupal\composer_manager\JsonFile.
 */

namespace Drupal\composer_manager;

/**
 * Reads and writes json files.
 */
final class JsonFile {

  /**
   * Reads and decodes a json file into an array.
   *
   * @return array
   *   The decoded json data.
   *
   * @throws \RuntimeException
   * @throws \UnexpectedValueException
   */
  public static function read($filename) {
    if (!is_readable($filename)) {
      throw new \RuntimeException(sprintf('%s is not readable.', $filename));
    }

    $json = file_get_contents($filename);
    if ($json === FALSE) {
      throw new \RuntimeException(sprintf('Could not read %s', $filename));
    }

    $data = json_decode($json, TRUE);
    if (JSON_ERROR_NONE !== json_last_error()) {
      throw new \UnexpectedValueException('Could not decode JSON: ' . json_last_error_msg());
    }

    return $data;
  }

  /**
   * Encodes and writes the provided json data to a file.
   *
   * @param string $filename
   *   Name of the file to write.
   * @param array $data
   *   The data to encode.
   *
   * @return int
   *   The number of bytes that were written to the file.
   *
   * @throws \RuntimeException
   * @throws \UnexpectedValueException
   */
  public static function write($filename, array $data) {
    if (!is_writable($filename)) {
      throw new \RuntimeException(sprintf('%s is not writable.', $filename));
    }

    // Strip empty config elements.
    foreach ($data as $key => $item) {
      if (is_array($item) && empty($item)) {
        unset($data[$key]);
      }
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (JSON_ERROR_NONE !== json_last_error()) {
      throw new \UnexpectedValueException('Could not encode JSON: ' . json_last_error_msg());
    }

    $bytes = file_put_contents($filename, $json);
    if ($bytes === FALSE) {
      throw new \RuntimeException(sprintf('Could not write to %s', $filename));
    }

    return $bytes;
  }

}
