# HTML Renderer

Converts documents to safe HTML.

## HtmlRenderer

```text
OpenSocial\RichTextJson\Renderer\HtmlRenderer
```

### Constructor

```php
public function __construct(?UrlSanitizer $urlSanitizer = null)
```

| Parameter       | Type            | Description                                    |
|-----------------|-----------------|------------------------------------------------|
| `$urlSanitizer` | `?UrlSanitizer` | Optional URL sanitizer (default: new instance) |

### Methods

#### renderDocument

Renders a document to HTML.

```php
public function renderDocument(RichTextDocument $document): string
```

| Parameter   | Type               | Description            |
|-------------|--------------------|------------------------|
| `$document` | `RichTextDocument` | The document to render |

**Returns:** `string` — The rendered HTML

---

## Output Mapping

| Node Type              | HTML Output                                      |
|------------------------|--------------------------------------------------|
| `root`                 | Children only (no wrapper)                       |
| `paragraph`            | `<p>...</p>`                                     |
| `heading` (level N)    | `<hN>...</hN>` (clamped to 1-6)                  |
| `list` (bullet)        | `<ul>...</ul>`                                   |
| `list` (number)        | `<ol>...</ol>` or `<ol start="N">...</ol>`       |
| `list-item`            | `<li>...</li>`                                   |
| `quote`                | `<blockquote>...</blockquote>`                   |
| `code`                 | `<pre><code>...</code></pre>`                    |
| `code` (with language) | `<pre><code class="language-X">...</code></pre>` |
| `text`                 | Escaped text with format tags                    |
| `text` (bold)          | `<strong>...</strong>`                           |
| `text` (italic)        | `<em>...</em>`                                   |
| `text` (underline)     | `<u>...</u>`                                     |
| `text` (strikethrough) | `<s>...</s>`                                     |
| `text` (superscript)   | `<sup>...</sup>`                                 |
| `text` (subscript)     | `<sub>...</sub>`                                 |
| `link`                 | `<a href="..." title="...">...</a>`              |
| `link` (dangerous URL) | `<span>...</span>`                               |
| `inline-code`          | `<code>...</code>`                               |
| `linebreak`            | `<br>`                                           |
| Unknown nodes          | (omitted)                                        |

---

## Security

### Text Escaping

All text content is escaped using:

```php
htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')
```

This prevents XSS attacks by escaping `<`, `>`, `&`, `"`, and `'`.

### URL Sanitization

Link URLs are sanitized to block dangerous schemes:

- `javascript:`
- `data:`
- `vbscript:`

Links with dangerous URLs render as `<span>` instead of `<a>`, preserving the link text but removing the href.

---

## UrlSanitizer

```text
OpenSocial\RichTextJson\Renderer\UrlSanitizer
```

### Methods

#### sanitize

Sanitizes a URL, returning empty string if dangerous.

```php
public function sanitize(string $url): string
```

| Parameter   | Type     | Description         |
|-------------|----------|---------------------|
| `$url`      | `string` | The URL to sanitize |

**Returns:** `string` — The URL or empty string if blocked

---

## Example

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Renderer\HtmlRenderer;

$document = RichTextDocument::fromJson($json);
$renderer = new HtmlRenderer();

$html = $renderer->renderDocument($document);
echo $html;
```

## See Also

- [HTML Importer](html-importer.md) — Convert HTML back to documents
- [How-To: Render HTML](../how-to/render-html.md)
