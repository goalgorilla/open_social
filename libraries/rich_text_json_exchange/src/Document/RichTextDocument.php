<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Document;

use OpenSocial\RichTextJson\Exception\InvalidDocumentException;
use OpenSocial\RichTextJson\Exception\JsonDecodeException;
use OpenSocial\RichTextJson\Exception\JsonEncodeException;
use OpenSocial\RichTextJson\Node\RootNode;

/**
 * Represents a rich text document.
 *
 * This is the main entry point for parsing and serializing
 * Rich Text JSON Exchange format documents.
 *
 * @phpstan-type RichTextDocumentArray array{root: array<string, mixed>}
 */
final class RichTextDocument {

  /**
   * The root node of the document.
   *
   * @var \OpenSocial\RichTextJson\Node\RootNode
   */
  private RootNode $root;

  /**
   * Creates a new RichTextDocument.
   *
   * @param \OpenSocial\RichTextJson\Node\RootNode $root
   *   The root node.
   */
  public function __construct(RootNode $root) {
    $this->root = $root;
  }

  /**
   * Creates a document from a JSON string.
   *
   * @param string $json
   *   The JSON string to parse.
   *
   * @return self
   *   The parsed document.
   *
   * @throws \OpenSocial\RichTextJson\Exception\JsonDecodeException
   *   If the JSON is malformed.
   * @throws \OpenSocial\RichTextJson\Exception\InvalidDocumentException
   *   If the document structure is invalid.
   */
  public static function fromJson(string $json): self {
    try {
      $data = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      throw new JsonDecodeException('Failed to parse document from JSON', $e);
    }

    if (!is_array($data)) {
      throw new InvalidDocumentException('Document must be a JSON object', '');
    }

    /** @var array<string, mixed> $data */
    return self::fromArray($data);
  }

  /**
   * Creates a document from an array.
   *
   * @param array<string, mixed> $data
   *   The document data.
   *
   * @return self
   *   The parsed document.
   *
   * @throws \OpenSocial\RichTextJson\Exception\InvalidDocumentException
   *   If the document structure is invalid.
   */
  public static function fromArray(array $data): self {
    if (!isset($data['root'])) {
      throw InvalidDocumentException::missingField('root', '');
    }

    if (!is_array($data['root'])) {
      throw InvalidDocumentException::invalidFieldType('root', 'an object', '');
    }

    /** @var array<string, mixed> $rootData */
    $rootData = $data['root'];
    $root = RootNode::fromArray($rootData);

    return new self($root);
  }

  /**
   * Gets the root node.
   *
   * @return \OpenSocial\RichTextJson\Node\RootNode
   *   The root node.
   */
  public function getRoot(): RootNode {
    return $this->root;
  }

  /**
   * Converts the document to an array representation.
   *
   * @return RichTextDocumentArray
   *   The array representation.
   */
  public function toArray(): array {
    return [
      'root' => $this->root->toArray(),
    ];
  }

  /**
   * Converts the document to a JSON string.
   *
   * @param int $flags
   *   JSON encoding flags (e.g., JSON_PRETTY_PRINT).
   *
   * @return string
   *   The JSON string.
   *
   * @throws \OpenSocial\RichTextJson\Exception\JsonEncodeException
   *   If the JSON encoding fails.
   */
  public function toJson(int $flags = 0): string {
    try {
      return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      throw new JsonEncodeException('Failed to encode document to JSON', $e);
    }
  }

}
