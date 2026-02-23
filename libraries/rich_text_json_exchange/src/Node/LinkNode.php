<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Node;

/**
 * Represents a link inline node.
 *
 * @phpstan-import-type NodeArray from \OpenSocial\RichTextJson\Node\NodeInterface
 * @phpstan-type LinkNodeArray array{
 *   type: 'link',
 *   version: int,
 *   url: string,
 *   title?: string,
 *   children?: list<NodeArray>
 * }&NodeArray
 */
final class LinkNode implements NodeInterface {

  /**
   * The node type.
   */
  private const TYPE = 'link';

  /**
   * Creates a new LinkNode.
   *
   * @param int $version
   *   The node version.
   * @param string $url
   *   The link URL.
   * @param string|null $title
   *   Optional link title.
   * @param array<int, NodeInterface> $children
   *   The child nodes (inline nodes).
   * @param array<string, mixed> $unknownFields
   *   Unknown fields to preserve for round-tripping.
   * @param bool $hasChildrenKey
   *   Whether the children key was explicitly present.
   * @param bool $hasVersion
   *   Whether the version field was explicitly provided.
   * @param bool $hasUrl
   *   Whether the url field was explicitly provided.
   */
  public function __construct(
    private readonly int $version,
    private readonly string $url,
    private readonly ?string $title = NULL,
    private readonly array $children = [],
    private readonly array $unknownFields = [],
    private readonly bool $hasChildrenKey = FALSE,
    private readonly bool $hasVersion = TRUE,
    private readonly bool $hasUrl = TRUE,
  ) {}

  /**
   * Creates a LinkNode from an array.
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
    $hasVersion = isset($data['version']) && is_int($data['version']);
    $version = 1;
    if ($hasVersion) {
      /** @var int $versionValue */
      $versionValue = $data['version'];
      $version = $versionValue;
    }

    $hasUrl = isset($data['url']) && is_string($data['url']);
    $url = '';
    if ($hasUrl) {
      /** @var string $urlValue */
      $urlValue = $data['url'];
      $url = $urlValue;
    }

    $title = NULL;
    if (isset($data['title']) && is_string($data['title'])) {
      $title = $data['title'];
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
    $knownFields = ['type', 'version', 'url', 'title', 'children'];
    $unknownFields = array_diff_key($data, array_flip($knownFields));

    return new self($version, $url, $title, $children, $unknownFields, $hasChildrenKey, $hasVersion, $hasUrl);
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
   * Gets the link URL.
   *
   * @return string
   *   The URL.
   */
  public function getUrl(): string {
    return $this->url;
  }

  /**
   * Gets the link title.
   *
   * @return string|null
   *   The title, or NULL if not set.
   */
  public function getTitle(): ?string {
    return $this->title;
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
   * Checks if the version field was explicitly provided.
   *
   * @return bool
   *   TRUE if version was provided.
   */
  public function hasVersion(): bool {
    return $this->hasVersion;
  }

  /**
   * Checks if the url field was explicitly provided.
   *
   * @return bool
   *   TRUE if url was provided.
   */
  public function hasUrl(): bool {
    return $this->hasUrl;
  }

  /**
   * Returns a new instance with the specified URL.
   *
   * @param string $url
   *   The new URL.
   *
   * @return self
   *   A new LinkNode instance.
   */
  public function withUrl(string $url): self {
    return new self(
      $this->version,
      $url,
      $this->title,
      $this->children,
      $this->unknownFields,
      $this->hasChildrenKey || $this->children !== [],
      $this->hasVersion,
      TRUE,
    );
  }

  /**
   * Returns a new instance with the specified title.
   *
   * @param string|null $title
   *   The new title, or NULL to remove.
   *
   * @return self
   *   A new LinkNode instance.
   */
  public function withTitle(?string $title): self {
    return new self(
      $this->version,
      $this->url,
      $title,
      $this->children,
      $this->unknownFields,
      $this->hasChildrenKey || $this->children !== [],
      $this->hasVersion,
      $this->hasUrl,
    );
  }

  /**
   * Returns a new instance with the specified children.
   *
   * @param array<int, NodeInterface> $children
   *   The new children.
   *
   * @return self
   *   A new LinkNode instance.
   */
  public function withChildren(array $children): self {
    return new self(
      $this->version,
      $this->url,
      $this->title,
      $children,
      $this->unknownFields,
      TRUE,
      $this->hasVersion,
      $this->hasUrl,
    );
  }

  /**
   * Returns a new instance with a child appended.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $child
   *   The child to append.
   *
   * @return self
   *   A new LinkNode instance.
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
   *   A new LinkNode instance.
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
   *   A new LinkNode instance.
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
   *   A new LinkNode instance.
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
   * @phpstan-return LinkNodeArray
   */
  public function toArray(): array {
    $result = [
      'type' => self::TYPE,
      'version' => $this->version,
      'url' => $this->url,
    ];

    if ($this->title !== NULL) {
      $result['title'] = $this->title;
    }

    // Add children if the key was originally present (even if empty).
    if ($this->hasChildrenKey || $this->children !== []) {
      $result['children'] = array_map(
        static fn(NodeInterface $child): array => $child->toArray(),
        $this->children,
      );
    }

    /** @phpstan-var LinkNodeArray $result */
    $result = array_merge($result, $this->unknownFields);
    return $result;
  }

}
