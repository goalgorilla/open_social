# Edit Nodes

How to modify existing documents using immutable operations.

## Change text content

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Node\TextNode;

$text = new TextNode(1, 'Original');
$updated = $text->withText('Updated');

echo $updated->getText(); // "Updated"
```

## Toggle bold

```php
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Bitmask\TextFormat;

$text = new TextNode(1, 'Some text');

// Add bold.
$format = TextFormat::none()->withBold(true);
$boldText = $text->withFormat($format->getValue());

// Remove bold (keep other formatting).
$existingFormat = TextFormat::fromNullable($boldText->getFormat()) ?? TextFormat::none();
$notBold = $boldText->withFormat($existingFormat->withBold(false)->getValue());
```

## Toggle italic

```php
$format = TextFormat::none()->withItalic(true);
$italicText = $text->withFormat($format->getValue());
```

## Toggle underline

```php
$format = TextFormat::none()->withUnderline(true);
$underlinedText = $text->withFormat($format->getValue());
```

## Toggle strikethrough

```php
$format = TextFormat::none()->withStrikethrough(true);
$struckText = $text->withFormat($format->getValue());
```

## Combine multiple formats

```php
$format = TextFormat::none()
    ->withBold(true)
    ->withItalic(true)
    ->withUnderline(true);

$formattedText = $text->withFormat($format->getValue());
```

## Set superscript

```php
use OpenSocial\RichTextJson\Bitmask\TextDetail;

$detail = TextDetail::none()->withSuperscript(true);
$superText = $text->withDetail($detail->getValue());
```

## Set subscript

```php
$detail = TextDetail::none()->withSubscript(true);
$subText = $text->withDetail($detail->getValue());
```

## Replace all children

```php
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;

$paragraph = new ParagraphNode(1, [
    new TextNode(1, 'Old content'),
]);

$newParagraph = $paragraph->withChildren([
    new TextNode(1, 'New content'),
]);
```

## Append a child

```php
$paragraph = $paragraph->appendChild(new TextNode(1, ' More text'));
```

## Insert child at position

```php
// Insert at index 1 (second position).
$paragraph = $paragraph->insertChild(1, new TextNode(1, 'Inserted'));
```

## Remove child by index

```php
// Remove the first child.
$paragraph = $paragraph->removeChild(0);
```

## Replace child at index

```php
// Replace the first child.
$paragraph = $paragraph->replaceChild(0, new TextNode(1, 'Replacement'));
```

## Update link URL

```php
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Node\TextNode;

$link = new LinkNode(1, 'https://old.com', null, [
    new TextNode(1, 'Click'),
]);

$updated = $link->withUrl('https://new.com');
```

## Update link title

```php
$withTitle = $link->withTitle('New tooltip');
$noTitle = $link->withTitle(null);  // Remove title
```

## Change heading level

```php
use OpenSocial\RichTextJson\Node\HeadingNode;
use OpenSocial\RichTextJson\Node\TextNode;

$h1 = new HeadingNode(1, 1, [new TextNode(1, 'Title')]);
$h2 = $h1->withLevel(2);
```

## Change list type

```php
use OpenSocial\RichTextJson\Node\ListNode;

// From bullet to numbered.
$numberedList = $bulletList->withListType('number');

// From numbered to bullet.
$bulletList = $numberedList->withListType('bullet');
```

## Change list start number

```php
// Start numbered list at 5.
$list = $list->withStart(5);

// Reset to default (1).
$list = $list->withStart(null);
```

## Edit nested structures

To edit deeply nested content, you need to rebuild the tree:

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;

$document = RichTextDocument::fromJson($json);
$root = $document->getRoot();

// Get the first paragraph.
$firstBlock = $root->getChildren()[0];

if ($firstBlock instanceof ParagraphNode) {
    // Get the first text node.
    $firstInline = $firstBlock->getChildren()[0];

    if ($firstInline instanceof TextNode) {
        // Modify the text.
        $newText = $firstInline->withText('Modified');

        // Rebuild upward.
        $newParagraph = $firstBlock->replaceChild(0, $newText);
        $newRoot = $root->replaceChild(0, $newParagraph);
        $newDocument = new RichTextDocument($newRoot);
    }
}
```

For complex transformations, consider using the [NodeTraverser](traverse-and-transform.md).

## See also

- [Create Documents](create-documents.md) — Build documents from scratch
- [Traverse and Transform](traverse-and-transform.md) — Bulk modifications
- [Reference: Bitmasks](../reference/bitmasks.md) — Format and detail values
