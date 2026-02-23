# Parse JSON

How to parse Rich Text JSON from various sources.

## Parse from a string

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\RichTextDocument;

$json = '{"root":{"type":"root","version":1,"children":[]}}';
$document = RichTextDocument::fromJson($json);
```

## Parse from an already-decoded array

If your JSON is already decoded (e.g., from a framework):

```php
$data = json_decode($json, true);
$document = RichTextDocument::fromArray($data);
```

## Handle parse errors

### Malformed JSON

`JsonDecodeException` is thrown when the JSON string is malformed:

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Exception\JsonDecodeException;

try {
    $document = RichTextDocument::fromJson('not valid json');
} catch (JsonDecodeException $e) {
    echo "JSON error: " . $e->getMessage();
}
```

### Invalid document structure

`InvalidDocumentException` is thrown when the JSON is valid but doesn't match the expected structure:

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Exception\InvalidDocumentException;

try {
    $document = RichTextDocument::fromJson('{"foo":"bar"}');
} catch (InvalidDocumentException $e) {
    echo "Structure error: " . $e->getMessage();
    echo " at " . $e->getPath();
}
```

### Handle both errors

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Exception\JsonDecodeException;
use OpenSocial\RichTextJson\Exception\InvalidDocumentException;

function parseDocument(string $json): ?RichTextDocument
{
    try {
        return RichTextDocument::fromJson($json);
    } catch (JsonDecodeException $e) {
        error_log("Malformed JSON: " . $e->getMessage());
        return null;
    } catch (InvalidDocumentException $e) {
        error_log("Invalid structure at {$e->getPath()}: " . $e->getMessage());
        return null;
    }
}
```

## Parse from a file

```php
$json = file_get_contents('/path/to/document.json');
if ($json === false) {
    throw new \RuntimeException('Could not read file');
}
$document = RichTextDocument::fromJson($json);
```

## Parse from an API response

```php
$response = $httpClient->get('https://api.example.com/document/123');
$data = json_decode($response->getBody(), true);

// If the API returns the document directly:
$document = RichTextDocument::fromArray($data);

// If the document is nested in a response wrapper:
$document = RichTextDocument::fromArray($data['document']);
```

## Parse from a database

```php
// Assuming a column containing JSON string
$row = $pdo->query("SELECT content FROM documents WHERE id = 1")->fetch();
$document = RichTextDocument::fromJson($row['content']);

// If stored as a JSON column (already decoded by driver):
$document = RichTextDocument::fromArray($row['content']);
```

## See also

- [Reference: Document](../reference/document.md) — Full API documentation
- [Reference: Exceptions](../reference/exceptions.md) — Exception details
