# Traverse and Transform

How to walk document trees and modify nodes using visitors.

## Create a visitor

Implement `NodeVisitorInterface`:

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Visitor\NodeVisitorInterface;

class MyVisitor implements NodeVisitorInterface
{
    public function enterNode(NodeInterface $node): NodeInterface|null
    {
        // Called before processing children.
        // Return the node (possibly transformed) or null to remove it.
        return $node;
    }

    public function leaveNode(NodeInterface $node): NodeInterface|null
    {
        // Called after processing children.
        // Return the node (possibly transformed) or null to remove it.
        return $node;
    }
}
```

## Traverse with a visitor

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Visitor\NodeTraverser;

$document = RichTextDocument::fromJson($json);

$traverser = new NodeTraverser([new MyVisitor()]);
$newRoot = $traverser->traverse($document->getRoot());

$newDocument = new RichTextDocument($newRoot);
```

## Replace nodes during traversal

Transform nodes by returning a different node:

```php
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

## Remove nodes during traversal

Return `null` to remove a node:

```php
use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Visitor\NodeVisitorInterface;

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

## Find all links

Collect nodes without modifying the tree:

```php
use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Visitor\NodeVisitorInterface;

class LinkCollector implements NodeVisitorInterface
{
    /** @var array<int, LinkNode> */
    public array $links = [];

    public function enterNode(NodeInterface $node): NodeInterface|null
    {
        if ($node instanceof LinkNode) {
            $this->links[] = $node;
        }
        return $node;
    }

    public function leaveNode(NodeInterface $node): NodeInterface|null
    {
        return $node;
    }
}

// Usage:
$collector = new LinkCollector();
$traverser = new NodeTraverser([$collector]);
$traverser->traverse($document->getRoot());

foreach ($collector->links as $link) {
    echo $link->getUrl() . "\n";
}
```

## Strip all formatting

Remove all text formatting:

```php
use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Visitor\NodeVisitorInterface;

class StripFormattingVisitor implements NodeVisitorInterface
{
    public function enterNode(NodeInterface $node): NodeInterface|null
    {
        if ($node instanceof TextNode) {
            return $node->withFormat(null)->withDetail(null);
        }
        return $node;
    }

    public function leaveNode(NodeInterface $node): NodeInterface|null
    {
        return $node;
    }
}
```

## Count nodes by type

```php
use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Visitor\NodeVisitorInterface;

class NodeCounter implements NodeVisitorInterface
{
    /** @var array<string, int> */
    public array $counts = [];

    public function enterNode(NodeInterface $node): NodeInterface|null
    {
        $type = $node->getType();
        $this->counts[$type] = ($this->counts[$type] ?? 0) + 1;
        return $node;
    }

    public function leaveNode(NodeInterface $node): NodeInterface|null
    {
        return $node;
    }
}

// Usage:
$counter = new NodeCounter();
$traverser = new NodeTraverser([$counter]);
$traverser->traverse($document->getRoot());

print_r($counter->counts);
// ['root' => 1, 'paragraph' => 3, 'text' => 5, ...]
```

## Multiple visitors

Apply multiple visitors in sequence:

```php
$traverser = new NodeTraverser([
    new StripFormattingVisitor(),
    new UppercaseVisitor(),
]);

$newRoot = $traverser->traverse($root);
```

Visitors are applied in order: each node passes through all visitors' `enterNode` methods, then children are processed, then all `leaveNode` methods.

## Replace links with plain text

Keep link text but remove the link wrapper:

```php
use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Visitor\NodeVisitorInterface;

class FlattenLinksVisitor implements NodeVisitorInterface
{
    public function enterNode(NodeInterface $node): NodeInterface|null
    {
        return $node;
    }

    public function leaveNode(NodeInterface $node): NodeInterface|null
    {
        if ($node instanceof LinkNode) {
            // Extract text from link children.
            $text = $this->extractText($node->getChildren());
            return new TextNode(1, $text);
        }
        return $node;
    }

    private function extractText(array $children): string
    {
        $text = '';
        foreach ($children as $child) {
            if ($child instanceof TextNode) {
                $text .= $child->getText();
            }
        }
        return $text;
    }
}
```

## See also

- [Edit Nodes](edit-nodes.md) — Manual node editing
- [Reference: Traverser](../reference/traverser.md) — Complete API
