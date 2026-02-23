<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Node;

/**
 * Represents a quote block node.
 *
 * @phpstan-import-type NodeArray from \OpenSocial\RichTextJson\Node\NodeInterface
 * @phpstan-type QuoteNodeArray array{
 *   type: 'quote',
 *   version: int,
 *   children?: list<NodeArray>
 * }&NodeArray
 */
final class QuoteNode implements NodeInterface {

  /**
   * The node type.
   */
  private const TYPE = 'quote';

  /**
   * Creates a new QuoteNode.
   *
   * @param int $version
   *   The node version.
   * @param array<int, NodeInterface> $children
   *   The child nodes (block nodes).
   * @param array<string, mixed> $unknownFields
   *   Unknown fields to preserve for round-tripping.
   * @param bool $hasChildrenKey
   *   Whether the children key was explicitly present.
   * @param bool $hasVersion
   *   Whether the version field was explicitly provided.
   */
  public function __construct(
    private readonly int $version,
    private readonly array $children = [],
    private readonly array $unknownFields = [],
    private readonly bool $hasChildrenKey = FALSE,
    private readonly bool $hasVersion = TRUE,
  ) {}

  /**
   * Creates a QuoteNode from an array.
   *
   * @param array<string, mixed> $data
   *   The node data.
   * @param \OpenSocial\RichTextJson\Node\NodeFactory $factory
   *   The node factory for creating children.
   *
   * @return self
   *   The created node.
   */
  public static function fromArray(array $data, NodeFactory $factory): self {
    $hasVersion = FALSE;
    $version = 1;
    if (isset($data['version']) && is_int($data['version'])) {
      $hasVersion = TRUE;
      $version = $data['version'];
    }

    $children = [];
    $hasChildrenKey = array_key_exists('children', $data);
    if ($hasChildrenKey && is_array($data['children'])) {
      foreach ($data['children'] as $childData) {
        if (is_array($childData)) {
          /** @var array<string, mixed> $childData */
          $children[] = $factory->createNode($childData);
        }
      }
    }

    // Collect unknown fields.
    $knownFields = ['type', 'version', 'children'];
    $unknownFields = array_diff_key($data, array_flip($knownFields));

    return new self($version, $children, $unknownFields, $hasChildrenKey, $hasVersion);
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
   * Gets the child nodes.
   *
   * @return array<int, NodeInterface>
   *   The child nodes.
   */
  public function getChildren(): array {
    return $this->children;
  }

  /**
   * Returns a new instance with the specified children.
   *
   * @param array<int, NodeInterface> $children
   *   The new children.
   *
   * @return self
   *   A new QuoteNode instance.
   */
  public function withChildren(array $children): self {
    return new self(
      $this->version,
      $children,
      $this->unknownFields,
      TRUE,
      $this->hasVersion,
    );
  }

  /**
   * Returns a new instance with a child appended.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $child
   *   The child to append.
   *
   * @return self
   *   A new QuoteNode instance.
   */
  public function appendChild(NodeInterface $child): self {
    $children = $this->children;
    $children[] = $child;
    return $this->withChildren($children);
  }

  /**
   * Returns a new instance with a child inserted at the specified index.
   *
   * @param int $index
   *   The index to insert at.
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $child
   *   The child to insert.
   *
   * @return self
   *   A new QuoteNode instance.
   *
   * @throws \InvalidArgumentException
   *   If the index is out of bounds.
   */
  public function insertChild(int $index, NodeInterface $child): self {
    if ($index < 0) {
      throw new \InvalidArgumentException('Index must be non-negative');
    }
    if ($index > count($this->children)) {
      throw new \InvalidArgumentException('Index out of bounds');
    }
    $children = $this->children;
    array_splice($children, $index, 0, [$child]);
    return $this->withChildren($children);
  }

  /**
   * Returns a new instance with the child at the specified index removed.
   *
   * @param int $index
   *   The index to remove.
   *
   * @return self
   *   A new QuoteNode instance.
   *
   * @throws \InvalidArgumentException
   *   If the index is out of bounds.
   */
  public function removeChild(int $index): self {
    if ($index < 0) {
      throw new \InvalidArgumentException('Index must be non-negative');
    }
    if ($index >= count($this->children)) {
      throw new \InvalidArgumentException('Index out of bounds');
    }
    $children = $this->children;
    array_splice($children, $index, 1);
    return $this->withChildren($children);
  }

  /**
   * Returns a new instance with the child at the specified index replaced.
   *
   * @param int $index
   *   The index to replace.
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $child
   *   The new child.
   *
   * @return self
   *   A new QuoteNode instance.
   *
   * @throws \InvalidArgumentException
   *   If the index is out of bounds.
   */
  public function replaceChild(int $index, NodeInterface $child): self {
    if ($index < 0) {
      throw new \InvalidArgumentException('Index must be non-negative');
    }
    if ($index >= count($this->children)) {
      throw new \InvalidArgumentException('Index out of bounds');
    }
    $children = $this->children;
    $children[$index] = $child;
    return $this->withChildren($children);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return QuoteNodeArray
   */
  public function toArray(): array {
    $result = [
      'type' => self::TYPE,
      'version' => $this->version,
    ];

    // Add children if the key was originally present (even if empty).
    if ($this->hasChildrenKey || $this->children !== []) {
      $result['children'] = array_map(
        static fn(NodeInterface $child): array => $child->toArray(),
        $this->children,
      );
    }

    /** @phpstan-var QuoteNodeArray $result */
    $result = array_merge($result, $this->unknownFields);
    return $result;
  }

}
