# Import HTML

How to convert HTML into documents.

## Basic import

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Renderer\HtmlImporter;

$html = '<p>Hello, <strong>world</strong>!</p>';

$importer = new HtmlImporter();
$document = $importer->fromHtml($html);

echo $document->toJson(JSON_PRETTY_PRINT);
```

## Handle malformed HTML

The importer uses PHP's DOMDocument, which is tolerant of malformed HTML:

```php
// Unclosed tags are handled gracefully.
$html = '<p>Unclosed paragraph';
$document = $importer->fromHtml($html);  // Works
```

## Supported elements

### Block elements

| HTML             | Node Type           |
|------------------|---------------------|
| `<p>`            | paragraph           |
| `<h1>` to `<h6>` | heading (level 1-6) |
| `<ul>`           | list (bullet)       |
| `<ol>`           | list (number)       |
| `<li>`           | list-item           |
| `<blockquote>`   | quote               |
| `<pre><code>`    | code                |

### Inline elements

| HTML                       | Effect               |
|----------------------------|----------------------|
| `<strong>`, `<b>`          | bold format          |
| `<em>`, `<i>`              | italic format        |
| `<u>`                      | underline format     |
| `<s>`, `<del>`, `<strike>` | strikethrough format |
| `<sup>`                    | superscript detail   |
| `<sub>`                    | subscript detail     |
| `<a>`                      | link                 |
| `<code>` (inline)          | inline-code          |
| `<br>`                     | linebreak            |

## Whitespace handling

Multiple whitespace characters are collapsed to single spaces:

```php
$html = '<p>Multiple     spaces</p>';
// Becomes: "Multiple spaces"
```

Newlines in HTML source are also normalized:

```php
$html = "<p>Line one\nLine two</p>";
// Becomes: "Line one Line two"
```

Use `<br>` for explicit line breaks.

## Text merging

Adjacent text nodes with the same formatting are merged:

```php
$html = '<p><strong>Hello</strong><strong> world</strong></p>';
// Becomes one text node: "Hello world" with bold format
```

## Code block language detection

The importer looks for `language-*` classes on code elements:

```php
$html = '<pre><code class="language-php">echo "hi";</code></pre>';
// Code node with language: "php"
```

## Import with start attribute

Ordered lists preserve the `start` attribute:

```php
$html = '<ol start="5"><li>Item</li></ol>';
// List node with start: 5
```

## When to use import

**Good uses:**
- Converting user-pasted HTML content
- Migrating from HTML-based storage
- Accepting content from rich text editors that output HTML

**Caution:**
- Import is best-effort, not lossless
- Complex HTML structures may not convert perfectly
- Sanitize untrusted HTML before importing

## Sanitize before importing

The importer does not sanitize HTML. Use a dedicated sanitizer for untrusted input:

```php
// Using a library like HTML Purifier
$clean = $htmlPurifier->purify($untrustedHtml);
$document = $importer->fromHtml($clean);
```

## Import from rich text editor

```php
// From a WYSIWYG editor like TinyMCE or CKEditor
$editorContent = $_POST['content'];

// Sanitize first!
$clean = $sanitizer->sanitize($editorContent);

// Then import
$document = $importer->fromHtml($clean);
$json = $document->toJson();

// Store the JSON for lossless retrieval
$database->save(['content' => $json]);
```

## See also

- [Render HTML](render-html.md) — Convert documents back to HTML
- [Reference: HTML Importer](../reference/html-importer.md) — Complete API
- [Explanation: Lossless Round-Tripping](../explanation/lossless-round-tripping.md) — Understanding round-trip limitations
