<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Node;

/**
 * Represents a text node containing plain text content.
 *
 * @phpstan-import-type NodeArray from \OpenSocial\RichTextJson\Node\NodeInterface
 * @phpstan-type TextNodeArray array{
 *   type: 'text',
 *   version: int,
 *   text: string,
 *   format?: int,
 *   detail?: int
 * }&NodeArray
 */
final class TextNode implements NodeInterface {

  /**
   * The node type.
   */
  private const TYPE = 'text';

  /**
   * Creates a new TextNode.
   *
   * @param int $version
   *   The node version.
   * @param string $text
   *   The text content.
   * @param int|null $format
   *   Optional format bitmask (bold, italic, etc.).
   * @param int|null $detail
   *   Optional detail bitmask (superscript, subscript).
   * @param array<string, mixed> $unknownFields
   *   Unknown fields to preserve for round-tripping.
   * @param bool $hasVersion
   *   Whether the version field was explicitly provided.
   * @param bool $hasText
   *   Whether the text field was explicitly provided.
   */
  public function __construct(
    private readonly int $version,
    private readonly string $text,
    private readonly ?int $format = NULL,
    private readonly ?int $detail = NULL,
    private readonly array $unknownFields = [],
    private readonly bool $hasVersion = TRUE,
    private readonly bool $hasText = TRUE,
  ) {}

  /**
   * Creates a TextNode from an array.
   *
   * @param array<string, mixed> $data
   *   The node data.
   *
   * @return self
   *   The created node.
   */
  public static function fromArray(array $data): self {
    $hasText = isset($data['text']) && is_string($data['text']);
    $text = '';
    if ($hasText) {
      /** @var string $textValue */
      $textValue = $data['text'];
      $text = $textValue;
    }

    $format = NULL;
    if (isset($data['format']) && is_int($data['format'])) {
      $format = $data['format'];
    }

    $detail = NULL;
    if (isset($data['detail']) && is_int($data['detail'])) {
      $detail = $data['detail'];
    }

    $hasVersion = isset($data['version']) && is_int($data['version']);
    $version = 1;
    if ($hasVersion) {
      /** @var int $versionValue */
      $versionValue = $data['version'];
      $version = $versionValue;
    }

    // Collect unknown fields.
    $knownFields = ['type', 'version', 'text', 'format', 'detail'];
    $unknownFields = array_diff_key($data, array_flip($knownFields));

    return new self($version, $text, $format, $detail, $unknownFields, $hasVersion, $hasText);
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return self::TYPE;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion(): int {
    return $this->version;
  }

  /**
   * Gets the text content.
   *
   * @return string
   *   The text content.
   */
  public function getText(): string {
    return $this->text;
  }

  /**
   * Gets the format bitmask.
   *
   * @return int|null
   *   The format bitmask, or NULL if not set.
   */
  public function getFormat(): ?int {
    return $this->format;
  }

  /**
   * Gets the detail bitmask.
   *
   * @return int|null
   *   The detail bitmask, or NULL if not set.
   */
  public function getDetail(): ?int {
    return $this->detail;
  }

  /**
   * Checks if the version field was explicitly provided.
   *
   * @return bool
   *   TRUE if version was provided.
   */
  public function hasVersion(): bool {
    return $this->hasVersion;
  }

  /**
   * Checks if the text field was explicitly provided.
   *
   * @return bool
   *   TRUE if text was provided.
   */
  public function hasText(): bool {
    return $this->hasText;
  }

  /**
   * Returns a new instance with the specified text.
   *
   * @param string $text
   *   The new text content.
   *
   * @return self
   *   A new TextNode instance.
   */
  public function withText(string $text): self {
    return new self(
      $this->version,
      $text,
      $this->format,
      $this->detail,
      $this->unknownFields,
      $this->hasVersion,
      TRUE,
    );
  }

  /**
   * Returns a new instance with the specified format.
   *
   * @param int|null $format
   *   The new format bitmask, or NULL to remove.
   *
   * @return self
   *   A new TextNode instance.
   */
  public function withFormat(?int $format): self {
    return new self(
      $this->version,
      $this->text,
      $format,
      $this->detail,
      $this->unknownFields,
      $this->hasVersion,
      $this->hasText,
    );
  }

  /**
   * Returns a new instance with the specified detail.
   *
   * @param int|null $detail
   *   The new detail bitmask, or NULL to remove.
   *
   * @return self
   *   A new TextNode instance.
   */
  public function withDetail(?int $detail): self {
    return new self(
      $this->version,
      $this->text,
      $this->format,
      $detail,
      $this->unknownFields,
      $this->hasVersion,
      $this->hasText,
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return TextNodeArray
   */
  public function toArray(): array {
    $result = [
      'type' => self::TYPE,
      'version' => $this->version,
      'text' => $this->text,
    ];

    if ($this->format !== NULL) {
      $result['format'] = $this->format;
    }

    if ($this->detail !== NULL) {
      $result['detail'] = $this->detail;
    }

    /** @phpstan-var TextNodeArray $result */
    $result = array_merge($result, $this->unknownFields);
    return $result;
  }

}
