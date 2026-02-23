<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Visitor;

use OpenSocial\RichTextJson\Node\NodeInterface;

/**
 * Interface for node visitors used with the NodeTraverser.
 */
interface NodeVisitorInterface {

  /**
   * Called when entering a node (before processing children).
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The node being visited.
   *
   * @return \OpenSocial\RichTextJson\Node\NodeInterface|null
   *   The (possibly transformed) node, or NULL to remove it.
   */
  public function enterNode(NodeInterface $node): NodeInterface|null;

  /**
   * Called when leaving a node (after processing children).
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The node being visited.
   *
   * @return \OpenSocial\RichTextJson\Node\NodeInterface|null
   *   The (possibly transformed) node, or NULL to remove it.
   */
  public function leaveNode(NodeInterface $node): NodeInterface|null;

}
