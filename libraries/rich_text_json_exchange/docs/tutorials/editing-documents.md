# Editing Documents

This tutorial teaches you how to modify documents using the library's immutable editing pattern.

## What you'll learn

- Understand immutability in this library
- Create nodes manually
- Modify nodes using `with*` methods
- Work with text formatting
- Build a document from scratch

## Understanding immutability

All nodes in this library are immutable. When you "edit" a node, you get a new instance with the changes applied. The original remains unchanged.

```php
$original = new TextNode(1, 'Hello');
$modified = $original->withText('Goodbye');

echo $original->getText(); // "Hello" — unchanged
echo $modified->getText(); // "Goodbye" — new instance
```

This pattern prevents accidental mutations and makes it easy to compare before/after states.

## Create a text node

Create a simple text node:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenSocial\RichTextJson\Node\TextNode;

$text = new TextNode(
    version: 1,
    text: 'Hello, world!',
);

echo $text->getText() . "\n";
```

## Create a paragraph with children

A paragraph contains inline nodes (text, links, etc.):

```php
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;

$text = new TextNode(1, 'This is a paragraph.');
$paragraph = new ParagraphNode(1, [$text]);

echo "Paragraph has " . count($paragraph->getChildren()) . " child(ren)\n";
```

## Append to the root

Use `appendChild()` to add blocks to the root:

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;

// Start with an empty root.
$root = new RootNode(1, []);

// Create a paragraph.
$text = new TextNode(1, 'First paragraph.');
$paragraph = new ParagraphNode(1, [$text]);

// Append it. This returns a NEW root.
$newRoot = $root->appendChild($paragraph);

echo "Original root has " . count($root->getChildren()) . " children\n";
echo "New root has " . count($newRoot->getChildren()) . " children\n";
```

Output:

```text
Original root has 0 children
New root has 1 children
```

## Modify text content

Use `withText()` to change the text:

```php
$text = new TextNode(1, 'Original text');
$updated = $text->withText('Updated text');

echo $updated->getText() . "\n"; // "Updated text"
```

## Add text formatting

Text formatting uses a bitmask. The `TextFormat` class helps you work with it:

```php
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Bitmask\TextFormat;

// Create a text node with bold formatting.
$format = TextFormat::none()->withBold(true);
$boldText = new TextNode(1, 'Bold text', $format->getValue());

// Check the formatting.
$textFormat = TextFormat::fromNullable($boldText->getFormat());
echo "Is bold: " . ($textFormat?->isBold() ? 'yes' : 'no') . "\n";
```

You can combine multiple formats:

```php
$format = TextFormat::none()
    ->withBold(true)
    ->withItalic(true);

$text = new TextNode(1, 'Bold and italic', $format->getValue());
```

## Modify existing formatting

Use `withFormat()` on a text node:

```php
$text = new TextNode(1, 'Plain text');

// Add bold.
$format = TextFormat::none()->withBold(true);
$boldText = $text->withFormat($format->getValue());

// Add italic to existing format.
$existingFormat = TextFormat::fromNullable($boldText->getFormat()) ?? TextFormat::none();
$newFormat = $existingFormat->withItalic(true);
$boldItalicText = $boldText->withFormat($newFormat->getValue());
```

## Build a complete document

Put it all together to build a document from scratch:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Bitmask\TextFormat;

// Create formatted text.
$boldFormat = TextFormat::none()->withBold(true);
$greeting = new TextNode(1, 'Hello, ', $boldFormat->getValue());
$name = new TextNode(1, 'world!');

// Create a paragraph with both text nodes.
$paragraph = new ParagraphNode(1, [$greeting, $name]);

// Create a root with the paragraph.
$root = new RootNode(1, [$paragraph]);

// Wrap in a document.
$document = new RichTextDocument($root);

// Output as JSON.
echo $document->toJson(JSON_PRETTY_PRINT) . "\n";
```

Output:

```json
{
    "root": {
        "type": "root",
        "version": 1,
        "children": [
            {
                "type": "paragraph",
                "version": 1,
                "children": [
                    {
                        "type": "text",
                        "version": 1,
                        "text": "Hello, ",
                        "format": 1
                    },
                    {
                        "type": "text",
                        "version": 1,
                        "text": "world!"
                    }
                ]
            }
        ]
    }
}
```

## Replace children

Use `replaceChild()` to swap a child at a specific index:

```php
$paragraph = new ParagraphNode(1, [
    new TextNode(1, 'First'),
    new TextNode(1, 'Second'),
]);

$newParagraph = $paragraph->replaceChild(0, new TextNode(1, 'Replaced'));

// Children are now: "Replaced", "Second"
```

## Insert and remove children

Insert at a specific position:

```php
$paragraph = $paragraph->insertChild(1, new TextNode(1, 'Inserted'));
```

Remove by index:

```php
$paragraph = $paragraph->removeChild(0);
```

## What's next

Now that you can create and edit documents, learn how to validate them in [Validating Documents](validating-documents.md).
