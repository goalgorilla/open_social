<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Node;

use OpenSocial\RichTextJson\Exception\InvalidDocumentException;

/**
 * Represents the root node of a rich text document.
 *
 * @phpstan-import-type NodeArray from \OpenSocial\RichTextJson\Node\NodeInterface
 * @phpstan-type RootNodeArray array{
 *   type: 'root',
 *   version: int,
 *   children?: list<NodeArray>
 * }&NodeArray
 */
final class RootNode implements NodeInterface {

  /**
   * The node type.
   */
  private const TYPE = 'root';

  /**
   * The node version.
   *
   * @var int
   */
  private int $version;

  /**
   * The child nodes.
   *
   * @var array<int, NodeInterface>
   */
  private array $children;

  /**
   * Whether the children key was explicitly present in the source data.
   *
   * @var bool
   */
  private bool $hasChildrenKey;

  /**
   * Unknown fields for forward compatibility.
   *
   * @var array<string, mixed>
   */
  private array $unknownFields;

  /**
   * Creates a new RootNode.
   *
   * @param int $version
   *   The node version.
   * @param array<int, NodeInterface> $children
   *   The child nodes.
   * @param array<string, mixed> $unknownFields
   *   Unknown fields to preserve for round-tripping.
   * @param bool $hasChildrenKey
   *   Whether the children key was explicitly present.
   */
  public function __construct(
    int $version,
    array $children = [],
    array $unknownFields = [],
    bool $hasChildrenKey = FALSE,
  ) {
    $this->version = $version;
    $this->children = $children;
    $this->unknownFields = $unknownFields;
    $this->hasChildrenKey = $hasChildrenKey;
  }

  /**
   * Creates a RootNode from an array.
   *
   * @param array<string, mixed> $data
   *   The node data.
   * @param \OpenSocial\RichTextJson\Node\NodeFactory|null $factory
   *   The node factory for creating children.
   * @param string $path
   *   The JSON pointer path for error messages.
   *
   * @return self
   *   The created node.
   *
   * @throws \OpenSocial\RichTextJson\Exception\InvalidDocumentException
   *   If the data is invalid.
   */
  public static function fromArray(array $data, ?NodeFactory $factory = NULL, string $path = '/root'): self {
    $factory ??= new NodeFactory();
    // Validate type.
    if (!isset($data['type'])) {
      throw InvalidDocumentException::missingField('type', $path);
    }
    if (!is_string($data['type'])) {
      throw InvalidDocumentException::invalidFieldType('type', 'a string', $path);
    }
    if ($data['type'] !== self::TYPE) {
      throw InvalidDocumentException::invalidFieldValue(
        'type',
        sprintf('must be "%s" for root node', self::TYPE),
        $path,
      );
    }

    // Validate version.
    if (!isset($data['version'])) {
      throw InvalidDocumentException::missingField('version', $path);
    }
    if (!is_int($data['version'])) {
      throw InvalidDocumentException::invalidFieldType('version', 'an integer', $path);
    }
    if ($data['version'] < 1) {
      throw InvalidDocumentException::invalidFieldValue('version', 'must be a positive integer', $path);
    }

    // Validate children if present.
    $children = [];
    $hasChildrenKey = array_key_exists('children', $data);
    if ($hasChildrenKey) {
      if (!is_array($data['children'])) {
        throw InvalidDocumentException::invalidFieldType('children', 'an array', $path);
      }
      foreach ($data['children'] as $index => $childData) {
        $childPath = sprintf('%s/children/%d', $path, $index);
        $children[] = self::parseChildNode($childData, $factory, $childPath);
      }
    }

    // Collect unknown fields.
    $knownFields = ['type', 'version', 'children'];
    $unknownFields = array_diff_key($data, array_flip($knownFields));

    return new self($data['version'], $children, $unknownFields, $hasChildrenKey);
  }

  /**
   * Parses a child node from array data.
   *
   * @param mixed $data
   *   The child node data.
   * @param \OpenSocial\RichTextJson\Node\NodeFactory $factory
   *   The node factory for creating children.
   * @param string $path
   *   The JSON pointer path for error messages.
   *
   * @return \OpenSocial\RichTextJson\Node\NodeInterface
   *   The parsed node.
   *
   * @throws \OpenSocial\RichTextJson\Exception\InvalidDocumentException
   *   If the data is invalid.
   */
  private static function parseChildNode(mixed $data, NodeFactory $factory, string $path): NodeInterface {
    if (!is_array($data)) {
      throw InvalidDocumentException::invalidFieldType('child', 'an object', $path);
    }

    /** @var array<string, mixed> $data */
    return $factory->createNode($data);
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
   *
   * RootNode enforces version presence during parsing, so this always
   * returns TRUE for successfully parsed root nodes.
   */
  public function hasVersion(): bool {
    return TRUE;
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
   *   A new RootNode instance.
   */
  public function withChildren(array $children): self {
    return new self(
      $this->version,
      $children,
      $this->unknownFields,
      TRUE,
    );
  }

  /**
   * Returns a new instance with a child appended.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $child
   *   The child to append.
   *
   * @return self
   *   A new RootNode instance.
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
   *   A new RootNode instance.
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
   *   A new RootNode instance.
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
   *   A new RootNode instance.
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
   * @phpstan-return RootNodeArray
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

    /** @phpstan-var RootNodeArray $result */
    $result = array_merge($result, $this->unknownFields);
    return $result;
  }

}
