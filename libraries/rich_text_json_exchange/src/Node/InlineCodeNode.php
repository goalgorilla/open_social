<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Node;

/**
 * Represents an inline code node.
 *
 * @phpstan-import-type NodeArray from \OpenSocial\RichTextJson\Node\NodeInterface
 * @phpstan-type InlineCodeNodeArray array{
 *   type: 'inline-code',
 *   version: int,
 *   code: string,
 *   language?: string
 * }&NodeArray
 */
final class InlineCodeNode implements NodeInterface {

  /**
   * The node type.
   */
  private const TYPE = 'inline-code';

  /**
   * Creates a new InlineCodeNode.
   *
   * @param int $version
   *   The node version.
   * @param string $code
   *   The code content.
   * @param string|null $language
   *   The programming language.
   * @param array<string, mixed> $unknownFields
   *   Unknown fields to preserve for round-tripping.
   * @param bool $hasVersion
   *   Whether the version field was explicitly provided.
   * @param bool $hasCode
   *   Whether the code field was explicitly provided.
   */
  public function __construct(
    private readonly int $version,
    private readonly string $code,
    private readonly ?string $language = NULL,
    private readonly array $unknownFields = [],
    private readonly bool $hasVersion = TRUE,
    private readonly bool $hasCode = TRUE,
  ) {}

  /**
   * Creates an InlineCodeNode from an array.
   *
   * @param array<string, mixed> $data
   *   The node data.
   *
   * @return self
   *   The created node.
   */
  public static function fromArray(array $data): self {
    $hasVersion = isset($data['version']) && is_int($data['version']);
    $version = 1;
    if ($hasVersion) {
      /** @var int $versionValue */
      $versionValue = $data['version'];
      $version = $versionValue;
    }

    $hasCode = isset($data['code']) && is_string($data['code']);
    $code = '';
    if ($hasCode) {
      /** @var string $codeValue */
      $codeValue = $data['code'];
      $code = $codeValue;
    }

    $language = NULL;
    if (isset($data['language']) && is_string($data['language'])) {
      $language = $data['language'];
    }

    // Collect unknown fields.
    $knownFields = ['type', 'version', 'code', 'language'];
    $unknownFields = array_diff_key($data, array_flip($knownFields));

    return new self($version, $code, $language, $unknownFields, $hasVersion, $hasCode);
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
   * Gets the code content.
   *
   * @return string
   *   The code content.
   */
  public function getCode(): string {
    return $this->code;
  }

  /**
   * Gets the programming language.
   *
   * @return string|null
   *   The language, or NULL if not set.
   */
  public function getLanguage(): ?string {
    return $this->language;
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
   * Checks if the code field was explicitly provided.
   *
   * @return bool
   *   TRUE if code was provided.
   */
  public function hasCode(): bool {
    return $this->hasCode;
  }

  /**
   * Returns a new instance with the specified code.
   *
   * @param string $code
   *   The new code content.
   *
   * @return self
   *   A new InlineCodeNode instance.
   */
  public function withCode(string $code): self {
    return new self(
      $this->version,
      $code,
      $this->language,
      $this->unknownFields,
      $this->hasVersion,
      TRUE,
    );
  }

  /**
   * Returns a new instance with the specified language.
   *
   * @param string|null $language
   *   The new language, or NULL to remove.
   *
   * @return self
   *   A new InlineCodeNode instance.
   */
  public function withLanguage(?string $language): self {
    return new self(
      $this->version,
      $this->code,
      $language,
      $this->unknownFields,
      $this->hasVersion,
      $this->hasCode,
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return InlineCodeNodeArray
   */
  public function toArray(): array {
    $result = [
      'type' => self::TYPE,
      'version' => $this->version,
      'code' => $this->code,
    ];

    if ($this->language !== NULL) {
      $result['language'] = $this->language;
    }

    /** @phpstan-var InlineCodeNodeArray $result */
    $result = array_merge($result, $this->unknownFields);
    return $result;
  }

}
