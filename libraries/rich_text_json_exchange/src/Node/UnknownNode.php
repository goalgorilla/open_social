<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Node;

/**
 * Represents an unknown node type for forward compatibility.
 *
 * This node preserves all fields from the original data, allowing
 * consumers to round-trip unknown node types without data loss.
 *
 * @phpstan-type UnknownNodeArray array<string, mixed>
 */
final class UnknownNode implements NodeInterface {

  /**
   * The original data array.
   *
   * @var UnknownNodeArray
   */
  private array $data;

  /**
   * Creates a new UnknownNode.
   *
   * @param array<string, mixed> $data
   *   The original node data including type and version.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    $type = $this->data['type'] ?? '';
    return is_string($type) ? $type : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion(): int {
    $version = $this->data['version'] ?? 0;
    return is_int($version) ? $version : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function hasVersion(): bool {
    return isset($this->data['version']) && is_int($this->data['version']);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return UnknownNodeArray
   */
  public function toArray(): array {
    return $this->data;
  }

}
