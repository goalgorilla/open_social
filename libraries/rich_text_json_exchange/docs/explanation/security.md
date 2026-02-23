# Security

When rendering rich text to HTML, security is critical. Malicious content could execute scripts in users' browsers or redirect them to harmful sites. This document explains what the library protects against, what it doesn't, and how to build a secure application.

## What the library protects against

### Cross-site scripting (XSS) via text

All text content is escaped when rendering to HTML:

```php
// Document contains: <script>alert('xss')</script>
// Rendered HTML: &lt;script&gt;alert('xss')&lt;/script&gt;
```

The `HtmlRenderer` uses `htmlspecialchars()` with appropriate flags:

```php
htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')
```

This escapes:
- `<` → `&lt;`
- `>` → `&gt;`
- `&` → `&amp;`
- `"` → `&quot;`
- `'` → `&#039;`

Escaped content displays as text, not as HTML elements or script.

### Dangerous URL schemes

Links with dangerous URL schemes are neutralized:

```php
// Link with javascript: URL
{"type": "link", "url": "javascript:alert('xss')", ...}

// Rendered as <span> instead of <a>
<span>link text</span>
```

The `UrlSanitizer` blocks:
- `javascript:` — Code execution
- `data:` — Can embed scripts
- `vbscript:` — Legacy script execution

The link content is preserved (users see the text), but the dangerous href is removed.

### Case and encoding bypass attempts

The sanitizer handles common bypass attempts:

```php
// Mixed case
"JaVaScRiPt:alert(1)"  → blocked

// HTML entities
"java&#115;cript:..."   → blocked (decoded first)

// Leading whitespace
"   javascript:..."     → blocked (trimmed first)
```

## What the library does NOT protect against

### Untrusted HTML input

The `HtmlImporter` does **not** sanitize HTML:

```php
// DANGEROUS: Importing untrusted HTML directly
$doc = $importer->fromHtml($untrustedUserInput);  // Don't do this!
```

The importer is designed for converting known-safe HTML (like output from a WYSIWYG editor you control). It doesn't strip malicious elements.

**Always sanitize untrusted HTML before importing:**

```php
// SAFE: Sanitize first, then import
$clean = $htmlSanitizer->sanitize($untrustedUserInput);
$doc = $importer->fromHtml($clean);
```

Use a dedicated HTML sanitizer like:
- [HTML Purifier](http://htmlpurifier.org/)
- [Symfony HtmlSanitizer](https://symfony.com/doc/current/html_sanitizer.html)

### Application-level concerns

The library doesn't protect against:

#### Phishing links

```php
// Valid-looking URL that's actually malicious
{"url": "https://faceb00k.com/login"}  // Note the zeros
```

The library allows any URL that isn't `javascript:`, `data:`, or `vbscript:`. Implement your own URL allowlist or reputation check if needed.

#### Spam and abuse

```php
// Spam content
{"type": "text", "text": "BUY CHEAP PILLS NOW!!!"}
```

Content moderation is your responsibility. Integrate with spam detection services as needed.

#### Denial of service

```php
// Extremely large document
{"root": {"children": [/* millions of nodes */]}}
```

The library doesn't limit document size. Implement size limits at your API layer.

#### SQL injection, command injection, etc.

The library only produces HTML output. How you use document data elsewhere (database queries, shell commands) is your responsibility. Use parameterized queries and proper escaping.

### Server-side rendering context

When rendering on the server for inclusion in a larger page, ensure the output context is safe:

```php
// In a template
<div class="content">
    <?= $renderer->renderDocument($doc) ?>  // Safe if $doc came from your parser
</div>
```

The rendered HTML is safe **within an HTML context**. Don't insert it into:
- JavaScript strings without JSON encoding
- CSS without CSS escaping
- URL parameters without URL encoding

## Best practices

### Validate input

Always parse and validate before processing:

```php
try {
    $doc = RichTextDocument::fromJson($input);
    $result = (new Validator())->validateDocument($doc);
    if (!$result->isValid()) {
        // Reject or repair
    }
} catch (JsonDecodeException | InvalidDocumentException $e) {
    // Reject malformed input
}
```

### Sanitize before import

When accepting HTML from users:

```php
$sanitizer = new \HTMLPurifier();
$clean = $sanitizer->purify($userHtml);
$doc = (new HtmlImporter())->fromHtml($clean);
```

### Store JSON, not HTML

Store the JSON representation, not pre-rendered HTML:

```php
// Store
$db->save(['content' => $doc->toJson()]);

// Render on demand
$doc = RichTextDocument::fromJson($row['content']);
$html = $renderer->renderDocument($doc);
```

This lets you:
- Re-render with updated logic
- Fix security issues by re-rendering
- Apply output escaping appropriate to context

### Use Content Security Policy

Add defense in depth with CSP headers:

```text
Content-Security-Policy: script-src 'self'; style-src 'self'
```

Even if XSS somehow gets through, CSP can prevent script execution.

### Limit capabilities

If your application doesn't need certain features, consider:
- Stripping links entirely before rendering
- Converting links to plain text with URLs shown
- Removing code blocks in contexts where they're inappropriate

## Security checklist

- [ ] HTML from untrusted sources is sanitized before import
- [ ] Document JSON is stored, not pre-rendered HTML
- [ ] Input size limits are enforced at the API layer
- [ ] Content Security Policy headers are configured
- [ ] URLs are validated against an allowlist if phishing is a concern
- [ ] Rendered HTML is used in appropriate context (HTML, not JS/CSS)

## Summary

The library provides:
- **Text escaping**: All text content is HTML-escaped
- **URL sanitization**: Dangerous schemes are blocked

The library does NOT provide:
- **HTML sanitization**: Use a dedicated sanitizer for untrusted HTML
- **Content moderation**: Implement your own spam/abuse detection
- **Size limits**: Enforce at your API layer

Security is a shared responsibility. The library handles rendering-time concerns; application-level security is up to you.
