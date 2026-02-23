<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Document;

use OpenSocial\RichTextJson\Exception\ValidationException;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Validation\Validator;

/**
 * Represents a validated rich text document.
 *
 * This class wraps a RichTextDocument and guarantees that the document
 * has passed validation. It can only be created through factory methods
 * that perform validation, providing compile-time (static analysis)
 * guarantees that the document is spec-compliant.
 *
 * Use this type in function signatures when you require a valid document:
 * @code
 * function render(ValidatedDocument $doc): string {
 *     // Can trust all nodes have required fields
 * }
 * @endcode
 *
 * @phpstan-import-type RichTextDocumentArray from RichTextDocument
 */
final class ValidatedDocument {

  /**
   * Creates a new ValidatedDocument.
   *
   * Private constructor - use factory methods to create instances.
   *
   * @param \OpenSocial\RichTextJson\Document\RichTextDocument $document
   *   The validated document.
   */
  private function __construct(
    private readonly RichTextDocument $document,
  ) {}

  /**
   * Creates a validated document from a JSON string.
   *
   * @param string $json
   *   The JSON string to parse.
   * @param \OpenSocial\RichTextJson\Validation\Validator|null $validator
   *   Optional validator instance. If not provided, a default validator
   *   will be used.
   *
   * @return self
   *   The validated document.
   *
   * @throws \OpenSocial\RichTextJson\Exception\JsonDecodeException
   *   If the JSON is malformed.
   * @throws \OpenSocial\RichTextJson\Exception\InvalidDocumentException
   *   If the document structure is invalid during parsing.
   * @throws \OpenSocial\RichTextJson\Exception\ValidationException
   *   If the document fails validation.
   */
  public static function fromJson(string $json, ?Validator $validator = NULL): self {
    $document = RichTextDocument::fromJson($json);
    return self::fromDocument($document, $validator);
  }

  /**
   * Creates a validated document from an array.
   *
   * @param array<string, mixed> $data
   *   The document data.
   * @param \OpenSocial\RichTextJson\Validation\Validator|null $validator
   *   Optional validator instance. If not provided, a default validator
   *   will be used.
   *
   * @return self
   *   The validated document.
   *
   * @throws \OpenSocial\RichTextJson\Exception\InvalidDocumentException
   *   If the document structure is invalid during parsing.
   * @throws \OpenSocial\RichTextJson\Exception\ValidationException
   *   If the document fails validation.
   */
  public static function fromArray(array $data, ?Validator $validator = NULL): self {
    $document = RichTextDocument::fromArray($data);
    return self::fromDocument($document, $validator);
  }

  /**
   * Creates a validated document from an existing RichTextDocument.
   *
   * @param \OpenSocial\RichTextJson\Document\RichTextDocument $document
   *   The document to validate.
   * @param \OpenSocial\RichTextJson\Validation\Validator|null $validator
   *   Optional validator instance. If not provided, a default validator
   *   will be used.
   *
   * @return self
   *   The validated document.
   *
   * @throws \OpenSocial\RichTextJson\Exception\ValidationException
   *   If the document fails validation.
   */
  public static function fromDocument(
    RichTextDocument $document,
    ?Validator $validator = NULL,
  ): self {
    $validator ??= new Validator();
    $result = $validator->validateDocument($document);

    if (!$result->isValid()) {
      throw new ValidationException($result->getErrors());
    }

    return new self($document);
  }

  /**
   * Gets the underlying RichTextDocument.
   *
   * @return \OpenSocial\RichTextJson\Document\RichTextDocument
   *   The document.
   */
  public function getDocument(): RichTextDocument {
    return $this->document;
  }

  /**
   * Gets the root node.
   *
   * @return \OpenSocial\RichTextJson\Node\RootNode
   *   The root node.
   */
  public function getRoot(): RootNode {
    return $this->document->getRoot();
  }

  /**
   * Converts the document to an array representation.
   *
   * @return RichTextDocumentArray
   *   The array representation.
   */
  public function toArray(): array {
    return $this->document->toArray();
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
    return $this->document->toJson($flags);
  }

}
