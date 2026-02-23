<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Node;

/**
 * Factory for creating node instances from array data.
 */
final class NodeFactory {

  /**
   * Known block node types.
   *
   * @var array<string, true>
   */
  private const BLOCK_TYPES = [
    'paragraph' => TRUE,
    'heading' => TRUE,
    'list' => TRUE,
    'list-item' => TRUE,
    'quote' => TRUE,
    'code' => TRUE,
  ];

  /**
   * Known inline node types.
   *
   * @var array<string, true>
   */
  private const INLINE_TYPES = [
    'text' => TRUE,
    'linebreak' => TRUE,
    'link' => TRUE,
    'inline-code' => TRUE,
  ];

  /**
   * Creates a node from array data.
   *
   * @param array<string, mixed> $data
   *   The node data.
   *
   * @return \OpenSocial\RichTextJson\Node\NodeInterface
   *   The created node.
   */
  public function createNode(array $data): NodeInterface {
    $type = $this->extractType($data);

    return match ($type) {
      'paragraph' => ParagraphNode::fromArray($data, $this),
      'heading' => HeadingNode::fromArray($data, $this),
      'list' => ListNode::fromArray($data, $this),
      'list-item' => ListItemNode::fromArray($data, $this),
      'quote' => QuoteNode::fromArray($data, $this),
      'code' => CodeNode::fromArray($data),
      'text' => TextNode::fromArray($data),
      'linebreak' => LinebreakNode::fromArray($data),
      'link' => LinkNode::fromArray($data, $this),
      'inline-code' => InlineCodeNode::fromArray($data),
      default => new UnknownNode($data),
    };
  }

  /**
   * Checks if a type is a known block type.
   *
   * @param string $type
   *   The node type.
   *
   * @return bool
   *   TRUE if the type is a known block type.
   */
  public function isBlockType(string $type): bool {
    return isset(self::BLOCK_TYPES[$type]);
  }

  /**
   * Checks if a type is a known inline type.
   *
   * @param string $type
   *   The node type.
   *
   * @return bool
   *   TRUE if the type is a known inline type.
   */
  public function isInlineType(string $type): bool {
    return isset(self::INLINE_TYPES[$type]);
  }

  /**
   * Extracts the type from node data.
   *
   * @param array<string, mixed> $data
   *   The node data.
   *
   * @return string
   *   The node type, or empty string if not found.
   */
  private function extractType(array $data): string {
    if (isset($data['type']) && is_string($data['type'])) {
      return $data['type'];
    }
    return '';
  }

}
