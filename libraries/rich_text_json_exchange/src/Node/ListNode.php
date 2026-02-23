<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Node;

/**
 * Represents a list block node.
 *
 * @phpstan-import-type NodeArray from \OpenSocial\RichTextJson\Node\NodeInterface
 * @phpstan-type ListNodeArray array{
 *   type: 'list',
 *   version: int,
 *   listType?: string,
 *   start?: int,
 *   children?: list<NodeArray>
 * }&NodeArray
 * @todo ListNodes should have ListItems, this should be enforced in tests, then we can type it.
 */
final class ListNode implements NodeInterface {

  /**
   * The node type.
   */
  private const TYPE = 'list';

  /**
   * Creates a new ListNode.
   *
   * @param int $version
   *   The node version.
   * @param string $listType
   *   The list type ('bullet' or 'number').
   * @param int|null $start
   *   The start number for numbered lists.
   * @param array<int, NodeInterface> $children
   *   The child nodes (list-item nodes).
   * @param array<string, mixed> $unknownFields
   *   Unknown fields to preserve for round-tripping.
   * @param bool $hasChildrenKey
   *   Whether the children key was explicitly present.
   * @param bool $hasListType
   *   Whether the listType field was explicitly provided.
   * @param bool $hasStart
   *   Whether the start field was explicitly provided.
   * @param bool $hasVersion
   *   Whether the version field was explicitly provided.
   */
  public function __construct(
    private readonly int $version,
    private readonly string $listType = 'bullet',
    private readonly ?int $start = NULL,
    private readonly array $children = [],
    private readonly array $unknownFields = [],
    private readonly bool $hasChildrenKey = FALSE,
    private readonly bool $hasListType = FALSE,
    private readonly bool $hasStart = FALSE,
    private readonly bool $hasVersion = TRUE,
  ) {}

  /**
   * Creates a ListNode from an array.
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

    $hasListType = isset($data['listType']) && is_string($data['listType']);
    $listType = 'bullet';
    if ($hasListType) {
      /** @var string $listTypeValue */
      $listTypeValue = $data['listType'];
      $listType = $listTypeValue;
    }

    $hasStart = isset($data['start']) && is_int($data['start']);
    $start = NULL;
    if ($hasStart) {
      /** @var int $startValue */
      $startValue = $data['start'];
      $start = $startValue;
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
    $knownFields = ['type', 'version', 'listType', 'start', 'children'];
    $unknownFields = array_diff_key($data, array_flip($knownFields));

    return new self(
      $version,
      $listType,
      $start,
      $children,
      $unknownFields,
      $hasChildrenKey,
      $hasListType,
      $hasStart,
      $hasVersion,
    );
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
   * Gets the list type.
   *
   * @return string
   *   The list type ('bullet' or 'number').
   */
  public function getListType(): string {
    return $this->listType;
  }

  /**
   * Gets the start number for numbered lists.
   *
   * @return int|null
   *   The start number, or NULL if not set.
   */
  public function getStart(): ?int {
    return $this->start;
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
   * Returns a new instance with the specified list type.
   *
   * @param string $listType
   *   The new list type.
   *
   * @return self
   *   A new ListNode instance.
   */
  public function withListType(string $listType): self {
    return new self(
      $this->version,
      $listType,
      $this->start,
      $this->children,
      $this->unknownFields,
      $this->hasChildrenKey || $this->children !== [],
      TRUE,
      $this->hasStart,
      $this->hasVersion,
    );
  }

  /**
   * Returns a new instance with the specified start number.
   *
   * @param int|null $start
   *   The new start number, or NULL to remove.
   *
   * @return self
   *   A new ListNode instance.
   */
  public function withStart(?int $start): self {
    return new self(
      $this->version,
      $this->listType,
      $start,
      $this->children,
      $this->unknownFields,
      $this->hasChildrenKey || $this->children !== [],
      $this->hasListType,
      $start !== NULL,
      $this->hasVersion,
    );
  }

  /**
   * Returns a new instance with the specified children.
   *
   * @param array<int, NodeInterface> $children
   *   The new children.
   *
   * @return self
   *   A new ListNode instance.
   */
  public function withChildren(array $children): self {
    return new self(
      $this->version,
      $this->listType,
      $this->start,
      $children,
      $this->unknownFields,
      TRUE,
      $this->hasListType,
      $this->hasStart,
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
   *   A new ListNode instance.
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
   *   A new ListNode instance.
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
   *   A new ListNode instance.
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
   *   A new ListNode instance.
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
   * @phpstan-return ListNodeArray
   */
  public function toArray(): array {
    $result = [
      'type' => self::TYPE,
      'version' => $this->version,
    ];

    // Only include listType if it was explicitly provided.
    if ($this->hasListType) {
      $result['listType'] = $this->listType;
    }

    // Only include start if it was explicitly provided.
    if ($this->hasStart && $this->start !== NULL) {
      $result['start'] = $this->start;
    }

    // Add children if the key was originally present (even if empty).
    if ($this->hasChildrenKey || $this->children !== []) {
      $result['children'] = array_map(
        static fn(NodeInterface $child): array => $child->toArray(),
        $this->children,
      );
    }

    /** @phpstan-var ListNodeArray $result */
    $result = array_merge($result, $this->unknownFields);
    return $result;
  }

}
