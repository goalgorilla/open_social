# HTML Conversion

This tutorial teaches you how to render documents to HTML and import HTML back into documents.

## What you'll learn

- Render a document to safe HTML
- Import HTML into a document
- Understand what survives the round-trip
- Know the limitations

## Render a document to HTML

The `HtmlRenderer` converts a document to HTML:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Renderer\HtmlRenderer;

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
}';

$document = RichTextDocument::fromJson($json);
$renderer = new HtmlRenderer();

$html = $renderer->renderDocument($document);
echo $html;
```

Output:

```html
<p><strong>Hello, </strong>world!</p>
```

## HTML is safely escaped

The renderer escapes all text content to prevent XSS attacks:

```php
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
                        "text": "<script>alert(1)</script>"
                    }
                ]
            }
        ]
    }
}';

$document = RichTextDocument::fromJson($json);
$html = (new HtmlRenderer())->renderDocument($document);
echo $html;
```

Output:

```html
<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>
```

URLs in links are also sanitized to block dangerous schemes like `javascript:`.

## Import HTML into a document

The `HtmlImporter` converts HTML into a document:

```php
use OpenSocial\RichTextJson\Renderer\HtmlImporter;

$html = '<p>This is <strong>bold</strong> and <em>italic</em> text.</p>';

$importer = new HtmlImporter();
$document = $importer->fromHtml($html);

echo $document->toJson(JSON_PRETTY_PRINT);
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
                        "text": "This is "
                    },
                    {
                        "type": "text",
                        "version": 1,
                        "text": "bold",
                        "format": 1
                    },
                    {
                        "type": "text",
                        "version": 1,
                        "text": " and "
                    },
                    {
                        "type": "text",
                        "version": 1,
                        "text": "italic",
                        "format": 2
                    },
                    {
                        "type": "text",
                        "version": 1,
                        "text": " text."
                    }
                ]
            }
        ]
    }
}
```

## Supported HTML elements

The importer handles these elements:

**Block elements:**
- `<p>` → paragraph
- `<h1>` through `<h6>` → heading
- `<ul>` → bullet list
- `<ol>` → numbered list
- `<li>` → list item
- `<blockquote>` → quote
- `<pre><code>` → code block

**Inline elements:**
- `<strong>`, `<b>` → bold
- `<em>`, `<i>` → italic
- `<u>` → underline
- `<s>`, `<del>`, `<strike>` → strikethrough
- `<sup>` → superscript
- `<sub>` → subscript
- `<a>` → link
- `<code>` (inline) → inline code
- `<br>` → linebreak

## Test the round-trip

Render a document, then import it back:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Renderer\HtmlRenderer;
use OpenSocial\RichTextJson\Renderer\HtmlImporter;

$json = '{
    "root": {
        "type": "root",
        "version": 1,
        "children": [
            {
                "type": "heading",
                "version": 1,
                "level": 2,
                "children": [
                    {"type": "text", "version": 1, "text": "Welcome"}
                ]
            },
            {
                "type": "paragraph",
                "version": 1,
                "children": [
                    {"type": "text", "version": 1, "text": "This is ", "format": 1},
                    {"type": "text", "version": 1, "text": "formatted", "format": 3},
                    {"type": "text", "version": 1, "text": " text."}
                ]
            }
        ]
    }
}';

$original = RichTextDocument::fromJson($json);

// Render to HTML.
$renderer = new HtmlRenderer();
$html = $renderer->renderDocument($original);
echo "HTML:\n$html\n\n";

// Import back.
$importer = new HtmlImporter();
$imported = $importer->fromHtml($html);

echo "Re-imported JSON:\n";
echo $imported->toJson(JSON_PRETTY_PRINT) . "\n";
```

## Understand the limitations

The HTML round-trip is **best-effort**, not lossless:

1. **Unknown nodes are lost**: Unknown node types are omitted during rendering and cannot be recovered during import.

2. **Unknown fields are lost**: Extra fields on nodes don't appear in HTML and are not restored.

3. **Version information**: All imported nodes get version 1.

4. **Whitespace normalization**: Multiple whitespace characters collapse to single spaces.

5. **Text merging**: Adjacent text nodes with the same formatting are merged.

For lossless storage, use JSON serialization. Use HTML conversion for display and user input.

## Complete example

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Renderer\HtmlRenderer;
use OpenSocial\RichTextJson\Renderer\HtmlImporter;

// Import from user-provided HTML.
$userHtml = '<p>User typed <strong>some</strong> content.</p>';
$importer = new HtmlImporter();
$document = $importer->fromHtml($userHtml);

// Work with the document.
$root = $document->getRoot();
foreach ($root->getChildren() as $block) {
    if ($block instanceof ParagraphNode) {
        echo "Paragraph with " . count($block->getChildren()) . " inline node(s)\n";
        foreach ($block->getChildren() as $inline) {
            if ($inline instanceof TextNode) {
                echo "  - \"" . $inline->getText() . "\"";
                if ($inline->getFormat() !== null) {
                    echo " (format: " . $inline->getFormat() . ")";
                }
                echo "\n";
            }
        }
    }
}

// Render back to HTML for display.
$renderer = new HtmlRenderer();
echo "\nRendered HTML:\n";
echo $renderer->renderDocument($document) . "\n";
```

Output:

```text
Paragraph with 3 inline node(s)
  - "User typed "
  - "some" (format: 1)
  - " content."

Rendered HTML:
<p>User typed <strong>some</strong> content.</p>
```

## What's next

You've completed the tutorials! For specific tasks, see the [How-To Guides](../how-to/). For complete API details, see the [Reference](../reference/). To understand design decisions, see [Explanation](../explanation/).
