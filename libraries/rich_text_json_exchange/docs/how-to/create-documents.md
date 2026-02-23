# Create Documents

How to build documents programmatically from scratch.

## Create a text node

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Node\TextNode;

$text = new TextNode(
    version: 1,
    text: 'Hello, world!',
);
```

With formatting:

```php
use OpenSocial\RichTextJson\Bitmask\TextFormat;

$format = TextFormat::none()->withBold(true)->withItalic(true);
$text = new TextNode(1, 'Bold italic text', $format->getValue());
```

## Create a paragraph

```php
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;

$text = new TextNode(1, 'Paragraph content');
$paragraph = new ParagraphNode(1, [$text]);
```

## Create a heading

```php
use OpenSocial\RichTextJson\Node\HeadingNode;
use OpenSocial\RichTextJson\Node\TextNode;

$text = new TextNode(1, 'Section Title');
$heading = new HeadingNode(
    version: 1,
    level: 2,
    children: [$text],
);
```

## Create a bullet list

```php
use OpenSocial\RichTextJson\Node\ListNode;
use OpenSocial\RichTextJson\Node\ListItemNode;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;

// Create list items (each contains block nodes).
$item1 = new ListItemNode(1, [
    new ParagraphNode(1, [new TextNode(1, 'First item')]),
]);
$item2 = new ListItemNode(1, [
    new ParagraphNode(1, [new TextNode(1, 'Second item')]),
]);

// Create bullet list.
$list = new ListNode(
    version: 1,
    listType: 'bullet',
    start: null,
    children: [$item1, $item2],
);
```

## Create a numbered list

```php
use OpenSocial\RichTextJson\Node\ListNode;

$list = new ListNode(
    version: 1,
    listType: 'number',
    start: null,  // Starts at 1 by default
    children: [$item1, $item2],
);

// Start at a specific number:
$list = new ListNode(
    version: 1,
    listType: 'number',
    start: 5,  // Starts at 5
    children: [$item1, $item2],
);
```

## Create a quote

```php
use OpenSocial\RichTextJson\Node\QuoteNode;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;

$paragraph = new ParagraphNode(1, [new TextNode(1, 'Quoted text')]);
$quote = new QuoteNode(1, [$paragraph]);
```

## Create a code block

```php
use OpenSocial\RichTextJson\Node\CodeNode;

$code = new CodeNode(
    version: 1,
    code: "function hello() {\n    return 'world';\n}",
    language: 'php',
);
```

## Create an inline code span

```php
use OpenSocial\RichTextJson\Node\InlineCodeNode;

$inlineCode = new InlineCodeNode(1, 'console.log()');
```

## Create a link

```php
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Node\TextNode;

$link = new LinkNode(
    version: 1,
    url: 'https://example.com',
    title: 'Example Site',  // Optional tooltip
    children: [new TextNode(1, 'Click here')],
);
```

## Create a linebreak

```php
use OpenSocial\RichTextJson\Node\LinebreakNode;

$linebreak = new LinebreakNode(1);
```

## Assemble a complete document

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\HeadingNode;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Bitmask\TextFormat;

// Create heading.
$heading = new HeadingNode(1, 1, [
    new TextNode(1, 'Welcome'),
]);

// Create paragraph with mixed content.
$boldFormat = TextFormat::none()->withBold(true);
$paragraph = new ParagraphNode(1, [
    new TextNode(1, 'This is '),
    new TextNode(1, 'important', $boldFormat->getValue()),
    new TextNode(1, '. Visit '),
    new LinkNode(1, 'https://example.com', null, [
        new TextNode(1, 'our site'),
    ]),
    new TextNode(1, ' for more.'),
]);

// Assemble document.
$root = new RootNode(1, [$heading, $paragraph]);
$document = new RichTextDocument($root);

echo $document->toJson(JSON_PRETTY_PRINT);
```

## See also

- [Edit Nodes](edit-nodes.md) — Modify existing documents
- [Reference: Nodes](../reference/nodes.md) — Complete node API
