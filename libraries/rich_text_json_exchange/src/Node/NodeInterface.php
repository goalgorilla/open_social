<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Node;

/**
 * Interface for all rich text nodes.
 *
 * @phpstan-type NodeArray array{
 *    type: string,
 *    version: int,
 *    ...<string, mixed>
 *  }
 */
interface NodeInterface {

  /**
   * Gets the node type.
   *
   * @return string
   *   The type identifier (e.g., 'root', 'paragraph', 'text').
   */
  public function getType(): string;

  /**
   * Gets the node version.
   *
   * @return int
   *   The version number (positive integer).
   */
  public function getVersion(): int;

  /**
   * Checks if the version field was explicitly provided in the source data.
   *
   * Per the specification, version is a required field on all nodes.
   * This method allows validators to detect missing version fields
   * even when a default value has been applied during parsing.
   *
   * @return bool
   *   TRUE if the version field was present in the source data.
   */
  public function hasVersion(): bool;

  /**
   * Converts the node to an array representation.
   *
   * This must include all original fields, including unknown ones,
   * to support lossless round-tripping.
   *
   * @return NodeArray
   *   The array representation.
   */
  public function toArray(): array;

}
