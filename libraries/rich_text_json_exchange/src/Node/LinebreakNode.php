<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Node;

/**
 * Represents a linebreak node.
 *
 * @phpstan-import-type NodeArray from \OpenSocial\RichTextJson\Node\NodeInterface
 * @phpstan-type LinebreakNodeArray array{
 *   type: 'linebreak',
 *   version: int,
 * }&NodeArray
 */
final class LinebreakNode implements NodeInterface {

  /**
   * The node type.
   */
  private const TYPE = 'linebreak';

  /**
   * Creates a new LinebreakNode.
   *
   * @param int $version
   *   The node version.
   * @param array<string, mixed> $unknownFields
   *   Unknown fields to preserve for round-tripping.
   * @param bool $hasVersion
   *   Whether the version field was explicitly provided.
   */
  public function __construct(
    private readonly int $version,
    private readonly array $unknownFields = [],
    private readonly bool $hasVersion = TRUE,
  ) {}

  /**
   * Creates a LinebreakNode from an array.
   *
   * @param array<string, mixed> $data
   *   The node data.
   *
   * @return self
   *   The created node.
   */
  public static function fromArray(array $data): self {
    $hasVersion = FALSE;
    $version = 1;
    if (isset($data['version']) && is_int($data['version'])) {
      $hasVersion = TRUE;
      $version = $data['version'];
    }

    // Collect unknown fields.
    $knownFields = ['type', 'version'];
    $unknownFields = array_diff_key($data, array_flip($knownFields));

    return new self($version, $unknownFields, $hasVersion);
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
   * {@inheritdoc}
   */
  public function hasVersion(): bool {
    return $this->hasVersion;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return LinebreakNodeArray
   */
  public function toArray(): array {
    $result = [
      'type' => self::TYPE,
      'version' => $this->version,
    ];

    /** @phpstan-var LinebreakNodeArray $result */
    $result = array_merge($result, $this->unknownFields);
    return $result;
  }

}
