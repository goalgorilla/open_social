<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Exception;

/**
 * Exception thrown when JSON decoding fails.
 */
class JsonDecodeException extends \RuntimeException {

  /**
   * Creates a new JsonDecodeException.
   *
   * @param string $message
   *   A contextual message describing what operation failed.
   * @param \JsonException $previous
   *   The underlying JsonException with technical details.
   */
  public function __construct(string $message, \JsonException $previous) {
    parent::__construct($message, $previous->getCode(), $previous);
  }

}
