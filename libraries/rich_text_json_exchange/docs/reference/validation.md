# Validation

Classes for validating document structure.

## Validator

Validates documents against structural rules.

```text
OpenSocial\RichTextJson\Validation\Validator
```

### Constructor

```php
public function __construct(?NodeFactory $nodeFactory = null)
```

| Parameter      | Type           | Description                                                |
|----------------|----------------|------------------------------------------------------------|
| `$nodeFactory` | `?NodeFactory` | Optional factory for type checking (default: new instance) |

### Methods

#### validateDocument

Validates a document.

```php
public function validateDocument(RichTextDocument $document): ValidationResult
```

| Parameter   | Type               | Description              |
|-------------|--------------------|--------------------------|
| `$document` | `RichTextDocument` | The document to validate |

**Returns:** `ValidationResult`

### Validation Rules

The validator enforces these structural rules:

| Rule                 | Description                                                                               |
|----------------------|-------------------------------------------------------------------------------------------|
| Required fields      | `type` and `version` must be present                                                      |
| Root children        | Must be block nodes (not inline)                                                          |
| Inline containers    | Paragraphs, headings, links can only contain inline nodes                                 |
| Block containers     | Quotes can only contain block nodes                                                       |
| List children        | Lists can only contain list-item nodes                                                    |
| Childless nodes      | Text, linebreak, inline-code must not have children                                       |
| Type-specific fields | Text requires `text`, link requires `url`, heading requires `level`, code requires `code` |

---

## ValidationResult

The result of validating a document.

```text
OpenSocial\RichTextJson\Validation\ValidationResult
```

### Constructor

```php
public function __construct(array $errors = [])
```

| Parameter | Type                     | Description           |
|-----------|--------------------------|-----------------------|
| `$errors` | `array<ValidationError>` | The validation errors |

### Methods

#### isValid

Checks if the validation passed.

```php
public function isValid(): bool
```

**Returns:** `bool` — `true` if no errors

#### getErrors

Gets all validation errors.

```php
public function getErrors(): array
```

**Returns:** `array<ValidationError>`

---

## ValidationError

A single validation error with location.

```text
OpenSocial\RichTextJson\Validation\ValidationError
```

### Constructor

```php
public function __construct(string $message, string $path)
```

| Parameter  | Type     | Description                            |
|------------|----------|----------------------------------------|
| `$message` | `string` | The error message                      |
| `$path`    | `string` | JSON pointer path where error occurred |

### Methods

#### getMessage

Gets the error message.

```php
public function getMessage(): string
```

**Returns:** `string`

#### getPath

Gets the JSON pointer path.

```php
public function getPath(): string
```

**Returns:** `string` — e.g., `/root/children/0`

---

## JSON Pointer Paths

Error paths use JSON pointer notation (RFC 6901):

| Path                          | Location                           |
|-------------------------------|------------------------------------|
| `/root`                       | The root node                      |
| `/root/children/0`            | First child of root                |
| `/root/children/0/children/2` | Third child of first child of root |

## Example

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Validation\Validator;

$document = RichTextDocument::fromJson($json);
$validator = new Validator();
$result = $validator->validateDocument($document);

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo $error->getMessage() . ' at ' . $error->getPath() . "\n";
    }
}
```

---

## ValidatedDocument

For a simpler workflow that combines parsing and validation, see
[ValidatedDocument](document.md#validateddocument). It throws
`ValidationException` on failure, avoiding manual result checking:

```php
use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Exception\ValidationException;

try {
    $validated = ValidatedDocument::fromJson($json);
    // Guaranteed valid
} catch (ValidationException $e) {
    // Access all errors via $e->getErrors()
}
```

## See Also

- [Document: ValidatedDocument](document.md#validateddocument) — Combined parse + validate
- [Exceptions: ValidationException](exceptions.md#validationexception) — Validation exception
- [How-To: Validate and Handle Errors](../how-to/validate-and-handle-errors.md)
