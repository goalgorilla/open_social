# Rich Text JSON Exchange (PHP)

A PHP 8.3+ library for working with
the [OpenSocial Rich Text JSON Exchange format](open-social-rich-text-json-exchange-format.md).
It parses JSON into strictly-typed DTOs, provides immutable editing APIs,
validates document structure, and supports lossless round-tripping—including
preservation of unknown nodes and fields for forward compatibility.

## What This Library Does

- **Parses** JSON into strictly-typed PHP DTOs
- **Validates** document structure against format rules
- **Preserves** unknown nodes, fields, and bitmask bits (forward compatibility)
- **Edits** documents immutably via `with*` methods
- **Serializes** back to JSON with lossless round-trip guarantees
- **Renders** documents to safe, escaped HTML
- **Imports** HTML into documents (best-effort conversion)

## What This Library Does NOT Do

- Provide a WYSIWYG editor or UI components
- Replace the specification—refer to the spec for format details
- Sanitize untrusted HTML—use a dedicated sanitizer before importing
- Define storage or transport mechanisms

## Installation

```bash
composer require goalgorilla/rich_text_json_exchange
```

## Quick Example

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\RichTextDocument;

$json = <<<JSON
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
            "text": "Hello, world!"
          }
        ]
      }
    ]
  }
}
JSON;

// Parse JSON into DTOs
$document = RichTextDocument::fromJson($json);

// Access the document tree
$root = $document->getRoot();
foreach ($root->getChildren() as $block) {
    echo "Block type: " . $block->getType() . "\n";
}

// Serialize back to JSON (lossless round-trip)
$output = $document->toJson(JSON_PRETTY_PRINT);
```

## Documentation

- **[Tutorials](docs/tutorials/)** — Step-by-step guides for learning the
  library
- **[How-To Guides](docs/how-to/)** — Task-oriented recipes for specific
  problems
- **[Reference](docs/reference/)** — Complete API documentation
- **[Explanation](docs/explanation/)** — Background and design concepts

## Specification

This library implements the **OpenSocial Rich Text JSON Exchange format**.
See [Open-Social-Rich-Text-JSON-exchange-format.pdf](open-social-rich-text-json-exchange-format.md)
for the normative specification.

## License

This library is licensed under the [GPL-2.0-or-later](LICENSE) license.
