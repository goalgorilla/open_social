# Render HTML

How to convert documents to safe HTML for display.

## Basic rendering

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

## Output structure

The renderer produces semantic HTML:

| Node Type            | HTML Output                    |
|----------------------|--------------------------------|
| root                 | (children only, no wrapper)    |
| paragraph            | `<p>...</p>`                   |
| heading (level N)    | `<hN>...</hN>`                 |
| list (bullet)        | `<ul>...</ul>`                 |
| list (number)        | `<ol>...</ol>`                 |
| list-item            | `<li>...</li>`                 |
| quote                | `<blockquote>...</blockquote>` |
| code                 | `<pre><code>...</code></pre>`  |
| text (bold)          | `<strong>...</strong>`         |
| text (italic)        | `<em>...</em>`                 |
| text (underline)     | `<u>...</u>`                   |
| text (strikethrough) | `<s>...</s>`                   |
| text (superscript)   | `<sup>...</sup>`               |
| text (subscript)     | `<sub>...</sub>`               |
| link                 | `<a href="...">...</a>`        |
| inline-code          | `<code>...</code>`             |
| linebreak            | `<br>`                         |

## Understand escaping

All text content is escaped using `htmlspecialchars()`:

```php
// Document with: <script>alert(1)</script>
// Renders as: &lt;script&gt;alert(1)&lt;/script&gt;
```

This prevents XSS attacks when displaying user content.

## Handle dangerous URLs

Links with dangerous URL schemes are sanitized:

```php
// javascript: URLs render as <span> instead of <a>
// The link content is preserved, but the href is removed.
```

Blocked schemes:
- `javascript:`
- `data:`
- `vbscript:`

Example:

```php
// Input link: {"type":"link","url":"javascript:alert(1)","children":[...]}
// Output: <span>link text</span>
```

## Unknown nodes behavior

Unknown node types are silently omitted:

```php
// Unknown node type "custom-widget" in document
// Output: (nothing—node is skipped)
```

The children of unknown nodes are also omitted. This is safe but means custom node types won't render.

## Code blocks with language

Code blocks with a language get a class:

```php
// Code node with language "php"
// Output: <pre><code class="language-php">...</code></pre>
```

This is compatible with syntax highlighters like Prism or Highlight.js.

## Numbered list with start

Numbered lists with a custom start use the `start` attribute:

```php
// List with start: 5
// Output: <ol start="5">...</ol>
```

## Render to string for storage

```php
$html = $renderer->renderDocument($document);
$database->save(['html_content' => $html]);
```

## Render with wrapper

If you need a wrapper element:

```php
$html = $renderer->renderDocument($document);
$wrapped = '<div class="rich-text-content">' . $html . '</div>';
```

## See also

- [Import HTML](import-html.md) — Convert HTML back to documents
- [Reference: HTML Renderer](../reference/html-renderer.md) — Complete API
- [Explanation: Security](../explanation/security.md) — Security considerations
