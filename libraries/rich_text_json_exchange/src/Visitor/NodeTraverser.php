<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Visitor;

use OpenSocial\RichTextJson\Node\HeadingNode;
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Node\ListItemNode;
use OpenSocial\RichTextJson\Node\ListNode;
use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\QuoteNode;
use OpenSocial\RichTextJson\Node\RootNode;

/**
 * Traverses a node tree and applies visitors to each node.
 */
final class NodeTraverser {

  /**
   * The visitors to apply.
   *
   * @var array<int, NodeVisitorInterface>
   */
  private array $visitors;

  /**
   * Creates a new NodeTraverser.
   *
   * @param array<int, NodeVisitorInterface> $visitors
   *   The visitors to apply.
   */
  public function __construct(array $visitors) {
    $this->visitors = $visitors;
  }

  /**
   * Traverses the node tree and applies visitors.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The root node to traverse.
   *
   * @return \OpenSocial\RichTextJson\Node\NodeInterface
   *   The (possibly transformed) root node.
   */
  public function traverse(NodeInterface $node): NodeInterface {
    $result = $this->traverseNode($node);
    // Root node should never be removed.
    return $result ?? $node;
  }

  /**
   * Traverses a single node and its children.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The node to traverse.
   *
   * @return \OpenSocial\RichTextJson\Node\NodeInterface|null
   *   The (possibly transformed) node, or NULL if removed.
   */
  private function traverseNode(NodeInterface $node): ?NodeInterface {
    // Apply enterNode for all visitors.
    foreach ($this->visitors as $visitor) {
      $node = $visitor->enterNode($node);
      if ($node === NULL) {
        return NULL;
      }
    }

    // Traverse and transform children.
    $node = $this->traverseChildren($node);

    // Apply leaveNode for all visitors.
    foreach ($this->visitors as $visitor) {
      $node = $visitor->leaveNode($node);
      if ($node === NULL) {
        return NULL;
      }
    }

    return $node;
  }

  /**
   * Traverses and transforms the children of a node.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The node whose children to traverse.
   *
   * @return \OpenSocial\RichTextJson\Node\NodeInterface
   *   The node with transformed children.
   */
  private function traverseChildren(NodeInterface $node): NodeInterface {
    $children = $this->getChildren($node);
    if ($children === NULL) {
      return $node;
    }

    $newChildren = [];
    $hasChanges = FALSE;

    foreach ($children as $index => $child) {
      $newChild = $this->traverseNode($child);
      if ($newChild === NULL) {
        // Child was removed.
        $hasChanges = TRUE;
      }
      elseif ($newChild !== $child) {
        // Child was transformed.
        $newChildren[] = $newChild;
        $hasChanges = TRUE;
      }
      else {
        // Child unchanged.
        $newChildren[] = $child;
      }
    }

    if (!$hasChanges) {
      return $node;
    }

    return $this->withChildren($node, $newChildren);
  }

  /**
   * Gets the children of a node.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The node.
   *
   * @return array<int, NodeInterface>|null
   *   The children, or NULL if the node has no children.
   */
  private function getChildren(NodeInterface $node): ?array {
    if ($node instanceof RootNode) {
      return $node->getChildren();
    }
    if ($node instanceof ParagraphNode) {
      return $node->getChildren();
    }
    if ($node instanceof HeadingNode) {
      return $node->getChildren();
    }
    if ($node instanceof LinkNode) {
      return $node->getChildren();
    }
    if ($node instanceof ListNode) {
      return $node->getChildren();
    }
    if ($node instanceof ListItemNode) {
      return $node->getChildren();
    }
    if ($node instanceof QuoteNode) {
      return $node->getChildren();
    }

    return NULL;
  }

  /**
   * Returns a new node with the specified children.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The original node.
   * @param array<int, NodeInterface> $children
   *   The new children.
   *
   * @return \OpenSocial\RichTextJson\Node\NodeInterface
   *   The new node with updated children.
   */
  private function withChildren(NodeInterface $node, array $children): NodeInterface {
    if ($node instanceof RootNode) {
      return $node->withChildren($children);
    }
    if ($node instanceof ParagraphNode) {
      return $node->withChildren($children);
    }
    if ($node instanceof HeadingNode) {
      return $node->withChildren($children);
    }
    if ($node instanceof LinkNode) {
      return $node->withChildren($children);
    }
    if ($node instanceof ListNode) {
      return $node->withChildren($children);
    }
    if ($node instanceof ListItemNode) {
      return $node->withChildren($children);
    }
    if ($node instanceof QuoteNode) {
      return $node->withChildren($children);
    }

    // Node doesn't support children, return as-is.
    return $node;
  }

}
