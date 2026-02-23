# HTML Importer

Converts HTML into documents (best-effort).

## HtmlImporter

```text
OpenSocial\RichTextJson\Renderer\HtmlImporter
```

### Constructor

```php
public function __construct()
```

No parameters.

### Methods

#### fromHtml

Imports HTML and returns a document.

```php
public function fromHtml(string $html): RichTextDocument
```

| Parameter   | Type     | Description        |
|-------------|----------|--------------------|
| `$html`     | `string` | The HTML to import |

**Returns:** `RichTextDocument`

---

## Input Mapping

### Block Elements

| HTML Element                     | Node Type                             |
|----------------------------------|---------------------------------------|
| `<p>`                            | `paragraph`                           |
| `<h1>` to `<h6>`                 | `heading` (level 1-6)                 |
| `<ul>`                           | `list` (listType: `bullet`)           |
| `<ol>`                           | `list` (listType: `number`)           |
| `<ol start="N">`                 | `list` (listType: `number`, start: N) |
| `<li>`                           | `list-item`                           |
| `<blockquote>`                   | `quote`                               |
| `<pre><code>`                    | `code`                                |
| `<pre><code class="language-X">` | `code` (language: X)                  |

### Inline Elements

| HTML Element                 | Effect                       |
|------------------------------|------------------------------|
| `<strong>`, `<b>`            | Bold format (bit 1)          |
| `<em>`, `<i>`                | Italic format (bit 2)        |
| `<u>`                        | Underline format (bit 4)     |
| `<s>`, `<del>`, `<strike>`   | Strikethrough format (bit 8) |
| `<sup>`                      | Superscript detail (bit 1)   |
| `<sub>`                      | Subscript detail (bit 2)     |
| `<a href="..." title="...">` | `link`                       |
| `<code>` (inline)            | `inline-code`                |
| `<br>`                       | `linebreak`                  |

---

## Behavior

### Whitespace Normalization

Multiple whitespace characters collapse to single spaces:

```text
"Multiple     spaces" → "Multiple spaces"
```

### Text Merging

Adjacent text nodes with identical formatting are merged:

```html
<strong>Hello</strong><strong> world</strong>
```

Becomes one text node: `"Hello world"` with bold format.

### Unknown Elements

Unknown block elements: children are extracted as blocks.

Unknown inline elements: children are extracted with current formatting.

### Empty Content

Empty HTML returns a document with an empty root:

```php
$importer->fromHtml('');  // RootNode with no children
```

### Malformed HTML

The importer uses DOMDocument with error suppression, tolerating malformed HTML.

---

## Limitations

- **Not lossless:** Unknown node types from the original document cannot be recovered
- **Version:** All imported nodes have version 1
- **Unknown fields:** Original unknown fields are not preserved
- **Complex nesting:** Some complex HTML structures may not convert perfectly

---

## Example

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Renderer\HtmlImporter;

$html = '<p>Hello, <strong>world</strong>!</p>';

$importer = new HtmlImporter();
$document = $importer->fromHtml($html);

echo $document->toJson(JSON_PRETTY_PRINT);
```

## See Also

- [HTML Renderer](html-renderer.md) — Convert documents to HTML
- [How-To: Import HTML](../how-to/import-html.md)
