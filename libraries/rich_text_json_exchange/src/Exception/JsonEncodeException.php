<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Exception;

/**
 * Exception thrown when JSON encoding fails.
 */
class JsonEncodeException extends \RuntimeException {

  /**
   * Creates a new JsonEncodeException.
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
