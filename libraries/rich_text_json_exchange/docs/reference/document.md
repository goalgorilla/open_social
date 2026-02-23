# Document

The main entry point for parsing and serializing Rich Text JSON documents.

## Class

```text
OpenSocial\RichTextJson\Document\RichTextDocument
```

## Constructor

```php
public function __construct(RootNode $root)
```

| Parameter | Type       | Description                   |
|-----------|------------|-------------------------------|
| `$root`   | `RootNode` | The root node of the document |

## Static Methods

### fromJson

Parses a JSON string into a document.

```php
public static function fromJson(string $json): self
```

| Parameter | Type     | Description              |
|-----------|----------|--------------------------|
| `$json`   | `string` | The JSON string to parse |

**Returns:** `RichTextDocument`

**Throws:**
- `JsonDecodeException` — If the JSON is malformed
- `InvalidDocumentException` — If the document structure is invalid

### fromArray

Creates a document from an already-decoded array.

```php
public static function fromArray(array $data): self
```

| Parameter  | Type                   | Description       |
|------------|------------------------|-------------------|
| `$data`    | `array<string, mixed>` | The document data |

**Returns:** `RichTextDocument`

**Throws:**
- `InvalidDocumentException` — If the document structure is invalid

## Instance Methods

### getRoot

Gets the root node of the document.

```php
public function getRoot(): RootNode
```

**Returns:** `RootNode`

### toArray

Converts the document to an array representation.

```php
public function toArray(): array
```

**Returns:** `array<string, mixed>` — The document as an associative array

### toJson

Converts the document to a JSON string.

```php
public function toJson(int $flags = 0): string
```

| Parameter | Type  | Description                                     |
|-----------|-------|-------------------------------------------------|
| `$flags`  | `int` | JSON encoding flags (e.g., `JSON_PRETTY_PRINT`) |

**Returns:** `string` — The JSON string

**Throws:**
- `JsonEncodeException` — If JSON encoding fails

## Example

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\RichTextDocument;

// Parse from JSON
$document = RichTextDocument::fromJson($json);

// Access the root
$root = $document->getRoot();

// Serialize back
$json = $document->toJson(JSON_PRETTY_PRINT);
```

## See Also

- [ValidatedDocument](#validateddocument) — Wrapper guaranteeing validation
- [Nodes](nodes.md) — Node types including `RootNode`
- [Exceptions](exceptions.md) — Exception types

---

# ValidatedDocument

A wrapper around `RichTextDocument` that guarantees the document has passed
validation. Use this type when you require a spec-compliant document.

## Class

```text
OpenSocial\RichTextJson\Document\ValidatedDocument
```

## Static Methods

### fromJson

Parses and validates a JSON string.

```php
public static function fromJson(string $json, ?Validator $validator = null): self
```

| Parameter    | Type         | Description                                |
|--------------|--------------|--------------------------------------------|
| `$json`      | `string`     | The JSON string to parse                   |
| `$validator` | `?Validator` | Optional validator (default: new instance) |

**Returns:** `ValidatedDocument`

**Throws:**
- `JsonDecodeException` — If the JSON is malformed
- `InvalidDocumentException` — If parsing fails
- `ValidationException` — If validation fails

### fromArray

Parses and validates an array.

```php
public static function fromArray(array $data, ?Validator $validator = null): self
```

| Parameter    | Type                   | Description                                |
|--------------|------------------------|--------------------------------------------|
| `$data`      | `array<string, mixed>` | The document data                          |
| `$validator` | `?Validator`           | Optional validator (default: new instance) |

**Returns:** `ValidatedDocument`

**Throws:**
- `InvalidDocumentException` — If parsing fails
- `ValidationException` — If validation fails

### fromDocument

Validates an existing `RichTextDocument`.

```php
public static function fromDocument(
    RichTextDocument $document,
    ?Validator $validator = null
): self
```

| Parameter    | Type               | Description                                |
|--------------|--------------------|--------------------------------------------|
| `$document`  | `RichTextDocument` | The document to validate                   |
| `$validator` | `?Validator`       | Optional validator (default: new instance) |

**Returns:** `ValidatedDocument`

**Throws:**
- `ValidationException` — If validation fails

## Instance Methods

### getDocument

Gets the underlying `RichTextDocument`.

```php
public function getDocument(): RichTextDocument
```

**Returns:** `RichTextDocument`

### getRoot

Gets the root node.

```php
public function getRoot(): RootNode
```

**Returns:** `RootNode`

### toArray

Converts to array representation.

```php
public function toArray(): array
```

**Returns:** `array<string, mixed>`

### toJson

Converts to JSON string.

```php
public function toJson(int $flags = 0): string
```

| Parameter | Type  | Description         |
|-----------|-------|---------------------|
| `$flags`  | `int` | JSON encoding flags |

**Returns:** `string`

**Throws:**
- `JsonEncodeException` — If encoding fails

## Example

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Exception\ValidationException;

// Parse and validate in one step
try {
    $validated = ValidatedDocument::fromJson($json);
    // Document is guaranteed valid here
    $root = $validated->getRoot();
} catch (ValidationException $e) {
    // Handle validation errors
    foreach ($e->getErrors() as $error) {
        echo $error->getMessage() . ' at ' . $error->getPath() . "\n";
    }
}
```

## When to Use

Use `ValidatedDocument` in function signatures when you require a valid document:

```php
function renderDocument(ValidatedDocument $doc): string
{
    // Can trust all nodes have required fields
    return $renderer->render($doc->getRoot());
}
```

Use `RichTextDocument` when validation is optional or handled separately:

```php
function analyzeDocument(RichTextDocument $doc): array
{
    // May work with invalid documents
    return extractMetadata($doc);
}
```
