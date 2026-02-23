# Preserve Unknown Content

How to maintain forward compatibility by preserving content the library doesn't recognize.

## What is preserved automatically

The library preserves three categories of unknown content:

1. **Unknown node types** — Wrapped in `UnknownNode`
2. **Unknown fields** — Stored in each node's `unknownFields` array
3. **Unknown bitmask bits** — Preserved when modifying format/detail

This allows documents from newer versions of the specification to round-trip through older library versions without data loss.

## How UnknownNode works

When the parser encounters an unrecognized node type, it wraps the entire node data in an `UnknownNode`:

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\UnknownNode;

$json = '{
    "root": {
        "type": "root",
        "version": 1,
        "children": [
            {
                "type": "future-widget",
                "version": 1,
                "customData": {"foo": "bar"}
            }
        ]
    }
}';

$document = RichTextDocument::fromJson($json);
$root = $document->getRoot();

$firstChild = $root->getChildren()[0];

if ($firstChild instanceof UnknownNode) {
    echo "Unknown type: " . $firstChild->getType() . "\n";
    echo "Version: " . $firstChild->getVersion() . "\n";
}

// Serialize back—the unknown node is fully preserved.
echo $document->toJson(JSON_PRETTY_PRINT);
```

Output includes the original `future-widget` node unchanged.

## Round-trip with unknown types

Unknown nodes survive the round-trip completely:

```php
$original = '{"root":{"type":"root","version":1,"children":[{"type":"custom","version":2,"data":"preserved"}]}}';

$document = RichTextDocument::fromJson($original);
$output = $document->toJson();

// $output equals $original (minus whitespace differences)
```

## Round-trip with unknown fields

Extra fields on known nodes are also preserved:

```php
$json = '{
    "root": {
        "type": "root",
        "version": 1,
        "children": [
            {
                "type": "paragraph",
                "version": 1,
                "futureField": "some value",
                "children": []
            }
        ]
    }
}';

$document = RichTextDocument::fromJson($json);

// Edit the paragraph.
$root = $document->getRoot();
$paragraph = $root->getChildren()[0];
$newParagraph = $paragraph->appendChild(new TextNode(1, 'Added text'));
$newRoot = $root->replaceChild(0, $newParagraph);
$newDocument = new RichTextDocument($newRoot);

// futureField is still present.
echo $newDocument->toJson(JSON_PRETTY_PRINT);
```

## Round-trip with unknown bitmask bits

Unknown bits in format/detail are preserved when modifying known bits:

```php
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Bitmask\TextFormat;

// Text with format=17 (bit 1 = bold, bit 16 = unknown future format)
$text = new TextNode(1, 'Text', 17);

// Toggle italic (bit 2) while preserving unknown bit 16.
$format = TextFormat::fromNullable($text->getFormat()) ?? TextFormat::none();
$newFormat = $format->withItalic(true);
$newText = $text->withFormat($newFormat->getValue());

echo $newText->getFormat();  // 19 (1 + 2 + 16)
```

The unknown bit 16 is preserved because `TextFormat` only modifies the bits it knows about.

## Avoid accidental data loss

### Don't rebuild nodes from scratch

If you rebuild a node, you lose unknown fields:

```php
// This loses unknown fields!
$newParagraph = new ParagraphNode(1, $paragraph->getChildren());

// This preserves unknown fields:
$newParagraph = $paragraph->withChildren($newChildren);
```

### Don't filter out UnknownNode

If you traverse and filter nodes, keep unknown nodes:

```php
// This loses unknown nodes!
$filtered = array_filter($children, fn($n) => $n instanceof ParagraphNode);

// This preserves them:
$filtered = array_filter($children, fn($n) =>
    $n instanceof ParagraphNode || $n instanceof UnknownNode
);
```

### Don't clear format/detail bitmasks unnecessarily

```php
// This loses unknown bits!
$text = $text->withFormat(1);  // Sets exactly bold, clears everything else

// This preserves unknown bits:
$format = TextFormat::fromNullable($text->getFormat()) ?? TextFormat::none();
$text = $text->withFormat($format->withBold(true)->getValue());
```

## Check for unknown content

Detect if a document contains unknown content:

```php
use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Node\UnknownNode;
use OpenSocial\RichTextJson\Visitor\NodeVisitorInterface;
use OpenSocial\RichTextJson\Visitor\NodeTraverser;

class UnknownContentDetector implements NodeVisitorInterface
{
    public bool $hasUnknownNodes = false;

    public function enterNode(NodeInterface $node): NodeInterface|null
    {
        if ($node instanceof UnknownNode) {
            $this->hasUnknownNodes = true;
        }
        return $node;
    }

    public function leaveNode(NodeInterface $node): NodeInterface|null
    {
        return $node;
    }
}

$detector = new UnknownContentDetector();
$traverser = new NodeTraverser([$detector]);
$traverser->traverse($document->getRoot());

if ($detector->hasUnknownNodes) {
    echo "Document contains unknown node types.\n";
}
```

## See also

- [Explanation: Forward Compatibility](../explanation/forward-compatibility.md) — Why this matters
- [Explanation: Lossless Round-Tripping](../explanation/lossless-round-tripping.md) — Round-trip guarantees
- [Reference: Nodes](../reference/nodes.md) — UnknownNode API
