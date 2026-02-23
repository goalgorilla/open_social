# Lossless Round-Tripping

A key design goal of this library is lossless round-tripping: if you parse a JSON document and immediately serialize it back to JSON, you get the same document. No information is lost in the process. This document explains what that means, what is preserved, and where the limits are.

## What "lossless" means

Lossless round-tripping means:

```php
$original = '{"root":{"type":"root","version":1,"children":[...]}}';

$document = RichTextDocument::fromJson($original);
$output = $document->toJson();

// $output represents the same document as $original
```

"Same document" means structurally equivalent—the same nodes, fields, and values. Whitespace and key ordering may differ (JSON doesn't guarantee these), but the data is identical.

## What is preserved

### Node types and versions

Every node's type and version survive the round-trip:

```json
{"type": "paragraph", "version": 1}
```

Parsed and serialized, you get the same type and version.

### All fields (known and unknown)

Known fields like `text`, `url`, `level` are preserved. But so are unknown fields:

```json
{"type": "paragraph", "version": 1, "futureField": "value"}
```

The `futureField` appears in the output even though the library doesn't understand it.

### Children structure

The children array is preserved exactly:

- Order is maintained
- Empty arrays stay empty
- The presence or absence of the `children` key is tracked

If the original had `"children": []`, the output will too. If the original omitted `children` entirely, the output will also omit it (unless children were added programmatically).

### Unknown node types

Entire nodes with unknown types are preserved via `UnknownNode`:

```json
{"type": "custom-widget", "version": 2, "config": {"theme": "dark"}}
```

This parses into an `UnknownNode` that serializes back to the exact same JSON.

### Bitmask values

Format and detail bitmasks preserve all bits, including unknown ones:

```json
{"type": "text", "version": 1, "text": "Hello", "format": 17}
```

If bit 16 is unknown to the library, it's still preserved. The output will have `"format": 17`.

## The JSON round-trip

The lossless guarantee applies to this cycle:

```text
JSON string → fromJson() → RichTextDocument → toJson() → JSON string
```

Or equivalently:

```text
Array → fromArray() → RichTextDocument → toArray() → Array
```

If you don't modify the document between parsing and serializing, the output matches the input.

## What about edits?

When you edit a document, lossless preservation still applies to the parts you don't touch:

```php
$doc = RichTextDocument::fromJson($json);
$root = $doc->getRoot();

// Add a new paragraph (doesn't touch existing content)
$newRoot = $root->appendChild($newParagraph);
$newDoc = new RichTextDocument($newRoot);

$output = $newDoc->toJson();
// Original nodes preserved, new paragraph added
```

The `with*` methods are designed to preserve unknown fields:

```php
$text = $text->withText('New content');
// Unknown fields from original $text are copied to the new instance
```

## HTML is NOT lossless

The HTML conversion is explicitly **not** lossless:

```php
$document = RichTextDocument::fromJson($json);
$html = (new HtmlRenderer())->renderDocument($document);
$imported = (new HtmlImporter())->fromHtml($html);

// $imported is NOT guaranteed to equal $document
```

Why not?

### Information loss in rendering

- **Unknown nodes are omitted**: HTML has no representation for `custom-widget`
- **Unknown fields disappear**: HTML doesn't encode `futureField`
- **Version information lost**: HTML has no version concept

### Best-effort import

- **Whitespace normalized**: Multiple spaces become one
- **Text merged**: Adjacent same-styled text nodes combine
- **Version defaults to 1**: All imported nodes have version 1
- **No unknown content**: Imported documents have no unknown nodes or fields

### When to use each

| Use Case      | Method                    | Lossless ?       |
|---------------|---------------------------|------------------|
| Storage       | `toJson()` / `fromJson()` | Yes              |
| API transport | `toJson()` / `fromJson()` | Yes              |
| Display       | `HtmlRenderer`            | N/A (one-way)    |
| User input    | `HtmlImporter`            | No (best-effort) |

Store documents as JSON for lossless retrieval. Use HTML only for display and user input.

## Testing round-trip behavior

You can verify lossless round-tripping in tests:

```php
public function testRoundTrip(): void
{
    $original = '{"root":{"type":"root","version":1,"children":[...]}}';

    $document = RichTextDocument::fromJson($original);
    $output = $document->toJson();

    // Parse both to arrays for comparison (avoids whitespace issues)
    $this->assertEquals(
        json_decode($original, true),
        json_decode($output, true)
    );
}
```

## Edge cases

### Key ordering

JSON object key order isn't semantically significant. Implementation details of the library may cause a certain consistent key order but this should not be relied upon. 

### Numeric precision

JSON numbers map to PHP integers or floats. For the fields this library uses (version, format, detail, level, start), all are integers and preserve exactly.

### Unicode

UTF-8 text survives round-tripping unchanged. The library doesn't normalize Unicode forms, so the exact byte sequence is preserved.

## Summary

Lossless round-tripping is a guarantee for JSON serialization:

- Parse and serialize without modification → identical output though key order may differ
- Edit with `with*` methods → unmodified parts preserved
- Unknown content → preserved automatically

HTML conversion is explicitly not lossless—use JSON for storage and transport when you need to preserve everything.
