# Traverser

Classes for walking and transforming document trees.

## NodeVisitorInterface

Interface for visitors used with NodeTraverser.

```text
OpenSocial\RichTextJson\Visitor\NodeVisitorInterface
```

### Methods

#### enterNode

Called when entering a node (before processing children).

```php
public function enterNode(NodeInterface $node): NodeInterface|null
```

| Parameter  | Type            | Description            |
|------------|-----------------|------------------------|
| `$node`    | `NodeInterface` | The node being visited |

**Returns:** `NodeInterface|null`
- Return the node (possibly transformed) to continue
- Return `null` to remove the node

#### leaveNode

Called when leaving a node (after processing children).

```php
public function leaveNode(NodeInterface $node): NodeInterface|null
```

| Parameter  | Type            | Description            |
|------------|-----------------|------------------------|
| `$node`    | `NodeInterface` | The node being visited |

**Returns:** `NodeInterface|null`
- Return the node (possibly transformed) to continue
- Return `null` to remove the node

---

## NodeTraverser

Traverses a node tree and applies visitors.

```text
OpenSocial\RichTextJson\Visitor\NodeTraverser
```

### Constructor

```php
public function __construct(array $visitors)
```

| Parameter   | Type                          | Description       |
|-------------|-------------------------------|-------------------|
| `$visitors` | `array<NodeVisitorInterface>` | Visitors to apply |

### Methods

#### traverse

Traverses the tree starting from a node.

```php
public function traverse(NodeInterface $node): NodeInterface
```

| Parameter | Type            | Description               |
|-----------|-----------------|---------------------------|
| `$node`   | `NodeInterface` | The root node to traverse |

**Returns:** `NodeInterface` — The (possibly transformed) root node

---

## Traversal Order

For each node:

1. Call `enterNode()` on all visitors (in order)
2. Recursively traverse children
3. Call `leaveNode()` on all visitors (in order)

If any visitor returns `null` at step 1 or 3, the node is removed and traversal stops for that subtree.

---

## Supported Node Types

The traverser handles children for these node types:

| Node Type       | Children     |
|-----------------|--------------|
| `RootNode`      | Block nodes  |
| `ParagraphNode` | Inline nodes |
| `HeadingNode`   | Inline nodes |
| `LinkNode`      | Inline nodes |
| `ListNode`      | ListItemNode |
| `ListItemNode`  | Block nodes  |
| `QuoteNode`     | Block nodes  |

Childless nodes (`TextNode`, `LinebreakNode`, `InlineCodeNode`, `CodeNode`, `UnknownNode`) are visited but have no children to traverse.

---

## Example: Simple Visitor

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Visitor\NodeVisitorInterface;

class UppercaseVisitor implements NodeVisitorInterface
{
    public function enterNode(NodeInterface $node): NodeInterface|null
    {
        if ($node instanceof TextNode) {
            return $node->withText(strtoupper($node->getText()));
        }
        return $node;
    }

    public function leaveNode(NodeInterface $node): NodeInterface|null
    {
        return $node;
    }
}
```

## Example: Using Traverser

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Visitor\NodeTraverser;

$document = RichTextDocument::fromJson($json);

$traverser = new NodeTraverser([new UppercaseVisitor()]);
$newRoot = $traverser->traverse($document->getRoot());

$newDocument = new RichTextDocument($newRoot);
```

## Example: Removing Nodes

```php
class RemoveLinksVisitor implements NodeVisitorInterface
{
    public function enterNode(NodeInterface $node): NodeInterface|null
    {
        if ($node instanceof LinkNode) {
            return null;  // Remove all links
        }
        return $node;
    }

    public function leaveNode(NodeInterface $node): NodeInterface|null
    {
        return $node;
    }
}
```

## Example: Multiple Visitors

```php
$traverser = new NodeTraverser([
    new StripFormattingVisitor(),
    new UppercaseVisitor(),
]);
```

Visitors are applied in sequence for each node.

## See Also

- [How-To: Traverse and Transform](../how-to/traverse-and-transform.md)
- [Nodes](nodes.md) — Node types
