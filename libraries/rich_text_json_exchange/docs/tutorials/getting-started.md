# Getting Started

This tutorial teaches you the basic workflow: parsing a JSON document, accessing its structure, and serializing it back to JSON.

## What you'll learn

- Parse JSON into a document
- Access the root node and its children
- Read node types and content
- Serialize back to JSON

## Install the library

```bash
composer require goalgorilla/rich_text_json_exchange
```

## Parse a document

Create a PHP file and add the following code:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenSocial\RichTextJson\Document\RichTextDocument;

// A simple document with one paragraph.
$json = '{
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
                        "text": "Hello, world!"
                    }
                ]
            }
        ]
    }
}';

$document = RichTextDocument::fromJson($json);
```

The `fromJson()` method parses the JSON string and returns a `RichTextDocument` containing typed DTOs.

## Access the root node

Every document has a root node that contains block-level children:

```php
$root = $document->getRoot();

echo "Root type: " . $root->getType() . "\n";
echo "Root version: " . $root->getVersion() . "\n";
echo "Number of children: " . count($root->getChildren()) . "\n";
```

Output:

```text
Root type: root
Root version: 1
Number of children: 1
```

## Iterate over children

The root contains block nodes (paragraphs, headings, lists, etc.). Let's iterate:

```php
foreach ($root->getChildren() as $index => $block) {
    echo "Child $index: " . $block->getType() . "\n";
}
```

Output:

```text
Child 0: paragraph
```

## Read text content

To read the text inside a paragraph, you need to access its children (inline nodes):

```php
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;

$firstBlock = $root->getChildren()[0];

if ($firstBlock instanceof ParagraphNode) {
    foreach ($firstBlock->getChildren() as $inline) {
        if ($inline instanceof TextNode) {
            echo "Text: " . $inline->getText() . "\n";
        }
    }
}
```

Output:

```text
Text: Hello, world!
```

## Serialize back to JSON

Convert the document back to JSON:

```php
$output = $document->toJson();
echo $output;
```

For readable output, pass `JSON_PRETTY_PRINT`:

```php
$output = $document->toJson(JSON_PRETTY_PRINT);
echo $output;
```

The output will match the original structure. This is a lossless round-trip—even unknown fields and node types are preserved.

## Complete example

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;

$json = '{
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
                        "text": "Hello, world!"
                    }
                ]
            }
        ]
    }
}';

// Parse.
$document = RichTextDocument::fromJson($json);

// Access.
$root = $document->getRoot();
echo "Document has " . count($root->getChildren()) . " block(s)\n";

foreach ($root->getChildren() as $block) {
    if ($block instanceof ParagraphNode) {
        foreach ($block->getChildren() as $inline) {
            if ($inline instanceof TextNode) {
                echo "Text: " . $inline->getText() . "\n";
            }
        }
    }
}

// Serialize.
echo "\nSerialized:\n";
echo $document->toJson(JSON_PRETTY_PRINT) . "\n";
```

## What's next

Now that you can parse, access, and serialize documents, learn how to modify them in [Editing Documents](editing-documents.md).
