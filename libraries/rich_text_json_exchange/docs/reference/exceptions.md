# Exceptions

Exception classes thrown by the library.

## JsonDecodeException

Thrown when JSON decoding fails.

```text
OpenSocial\RichTextJson\Exception\JsonDecodeException
```

**Extends:** `\RuntimeException`

### When Thrown

- `RichTextDocument::fromJson()` when the JSON string is malformed

### Constructor

```php
public function __construct(string $message, \JsonException $previous)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$message` | `string` | A contextual message describing what operation failed |
| `$previous` | `\JsonException` | The underlying JsonException with technical details |

### Message Format

```
Failed to parse document from JSON
```

### Example

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Exception\JsonDecodeException;

try {
    $document = RichTextDocument::fromJson('invalid json');
} catch (JsonDecodeException $e) {
    echo $e->getMessage();
    // "Failed to parse document from JSON"

    echo $e->getPrevious()->getMessage();
    // "Syntax error"
}
```

---

## JsonEncodeException

Thrown when JSON encoding fails.

```
OpenSocial\RichTextJson\Exception\JsonEncodeException
```

**Extends:** `\RuntimeException`

### When Thrown

- `RichTextDocument::toJson()` when JSON encoding fails

### Constructor

```php
public function __construct(string $message, \JsonException $previous)
```

| Parameter    | Type             | Description                                           |
|--------------|------------------|-------------------------------------------------------|
| `$message`   | `string`         | A contextual message describing what operation failed |
| `$previous`  | `\JsonException` | The underlying JsonException with technical details   |

### Message Format

```
Failed to encode document to JSON
```

### Example

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Exception\JsonEncodeException;

try {
    $json = $document->toJson();
} catch (JsonEncodeException $e) {
    echo $e->getMessage();
    // "Failed to encode document to JSON"

    echo $e->getPrevious()->getMessage();
    // Technical details from JsonException
}
```

---

## InvalidDocumentException

Thrown when document structure is invalid.

```
OpenSocial\RichTextJson\Exception\InvalidDocumentException
```

**Extends:** `\RuntimeException`

### When Thrown

- `RichTextDocument::fromJson()` when JSON is valid but structure is wrong
- `RichTextDocument::fromArray()` when structure is wrong
- Node `fromArray()` methods when required fields are missing or invalid

### Constructor

```php
public function __construct(
    string $message,
    string $path = '',
    int $code = 0,
    ?\Throwable $previous = null
)
```

| Parameter   | Type          | Description |
|-------------|---------------|-------------|
| `$message`  | `string`      | The error message |
| `$path`     | `string`      | JSON pointer path where error occurred |
| `$code`     | `int`         | Error code |
| `$previous` | `?\Throwable` | Previous exception |

### Methods

#### getPath

Gets the JSON pointer path where the error occurred.

```php
public function getPath(): string
```

**Returns:** `string` — e.g., `/root`, `/root/children/0`

### Static Methods

#### missingField

Creates an exception for a missing required field.

```php
public static function missingField(string $field, string $path = ''): self
```

| Parameter  | Type     | Description       |
|------------|----------|-------------------|
| `$field`   | `string` | The field name    |
| `$path`    | `string` | JSON pointer path |

**Message:** `Missing required field "{field}" at {path}`

#### invalidFieldType

Creates an exception for an invalid field type.

```php
public static function invalidFieldType(string $field, string $expectedType, string $path = ''): self
```

| Parameter       | Type     | Description               |
|-----------------|----------|---------------------------|
| `$field`        | `string` | The field name            |
| `$expectedType` | `string` | Expected type description |
| `$path`         | `string` | JSON pointer path         |

**Message:** `Field "{field}" must be {expectedType} at {path}`

#### invalidFieldValue

Creates an exception for an invalid field value.

```php
public static function invalidFieldValue(string $field, string $reason, string $path = ''): self
```

| Parameter   | Type     | Description              |
|-------------|----------|--------------------------|
| `$field`    | `string` | The field name           |
| `$reason`   | `string` | Why the value is invalid |
| `$path`     | `string` | JSON pointer path        |

**Message:** `Invalid value for field "{field}": {reason} at {path}`

### Example

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Exception\InvalidDocumentException;

try {
    $document = RichTextDocument::fromJson('{"foo": "bar"}');
} catch (InvalidDocumentException $e) {
    echo $e->getMessage();
    // "Missing required field "root" at "

    echo $e->getPath();
    // ""
}

try {
    $document = RichTextDocument::fromJson('{"root": {"type": "wrong"}}');
} catch (InvalidDocumentException $e) {
    echo $e->getMessage();
    // 'Invalid value for field "type": must be "root" for root node at /root'

    echo $e->getPath();
    // "/root"
}
```

---

## Handling Both Exceptions

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Exception\JsonDecodeException;
use OpenSocial\RichTextJson\Exception\InvalidDocumentException;

function parseDocument(string $json): ?RichTextDocument
{
    try {
        return RichTextDocument::fromJson($json);
    } catch (JsonDecodeException $e) {
        // Malformed JSON
        error_log('JSON error: ' . $e->getMessage());
        return null;
    } catch (InvalidDocumentException $e) {
        // Valid JSON but wrong structure
        error_log('Structure error at ' . $e->getPath() . ': ' . $e->getMessage());
        return null;
    }
}
```

---

## ValidationException

Thrown when document validation fails.

```text
OpenSocial\RichTextJson\Exception\ValidationException
```

**Extends:** `\RuntimeException`

### When Thrown

- `ValidatedDocument::fromJson()` when validation fails
- `ValidatedDocument::fromArray()` when validation fails
- `ValidatedDocument::fromDocument()` when validation fails

### Constructor

```php
public function __construct(
    array $errors,
    int $code = 0,
    ?\Throwable $previous = null
)
```

| Parameter   | Type                     | Description           |
|-------------|--------------------------|-----------------------|
| `$errors`   | `array<ValidationError>` | The validation errors |
| `$code`     | `int`                    | Error code            |
| `$previous` | `?\Throwable`            | Previous exception    |

### Methods

#### getErrors

Gets all validation errors.

```php
public function getErrors(): array
```

**Returns:** `array<ValidationError>`

### Message Format

Single error:
```text
Document validation failed: Missing required field "version" at /root/children/0
```

Multiple errors:
```text
Document validation failed with 3 errors: Error 1 at path1; Error 2 at path2; ...
```

### Example

```php
use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Exception\ValidationException;

try {
    $validated = ValidatedDocument::fromJson($json);
} catch (ValidationException $e) {
    echo "Validation failed:\n";

    foreach ($e->getErrors() as $error) {
        echo sprintf(
            "  - %s at %s\n",
            $error->getMessage(),
            $error->getPath()
        );
    }
}
```

---

## See Also

- [Document](document.md) — Where exceptions are thrown
- [Validation](validation.md) — Validation classes
- [How-To: Parse JSON](../how-to/parse-json.md) — Error handling examples
- [How-To: Validate and Handle Errors](../how-to/validate-and-handle-errors.md)
