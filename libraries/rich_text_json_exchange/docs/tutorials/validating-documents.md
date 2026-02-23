# Validating Documents

This tutorial teaches you how to validate documents against structural rules and interpret validation errors.

## What you'll learn

- Create a Validator
- Validate a document
- Check if a document is valid
- Read validation errors and their paths
- Understand common validation rules

## Create a Validator

The `Validator` class checks documents against structural rules:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenSocial\RichTextJson\Validation\Validator;

$validator = new Validator();
```

## Validate a well-formed document

Parse a document and validate it:

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Validation\Validator;

$json = '{
    "root": {
        "type": "root",
        "version": 1,
        "children": [
            {
                "type": "paragraph",
                "version": 1,
                "children": [
                    {
                        "type": "text",
                        "version": 1,
                        "text": "Hello, world!"
                    }
                ]
            }
        ]
    }
}';

$document = RichTextDocument::fromJson($json);
$validator = new Validator();

$result = $validator->validateDocument($document);

if ($result->isValid()) {
    echo "Document is valid!\n";
} else {
    echo "Document has errors.\n";
}
```

Output:

```text
Document is valid!
```

## Create an invalid document

Let's create a document with a structural error—placing an inline node directly under the root (which should only contain block nodes):

```php
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\TextNode;

// This is invalid: text nodes cannot be direct children of root.
$text = new TextNode(1, 'This should not be here');
$root = new RootNode(1, [$text]);
$document = new RichTextDocument($root);
```

## Check validation errors

Validate the invalid document:

```php
$validator = new Validator();
$result = $validator->validateDocument($document);

if (!$result->isValid()) {
    echo "Validation failed!\n\n";

    foreach ($result->getErrors() as $error) {
        echo "Error: " . $error->getMessage() . "\n";
        echo "Path:  " . $error->getPath() . "\n\n";
    }
}
```

Output:

```text
Validation failed!

Error: Inline node "text" not allowed under root; expected block node
Path:  /root/children/0
```

## Understanding JSON pointer paths

Error paths use JSON pointer notation:

- `/root` — the root node
- `/root/children/0` — the first child of root
- `/root/children/0/children/2` — the third child of the first child of root

These paths help you locate exactly where the error occurred.

## Common validation rules

The validator enforces these structural rules:

1. **Root children must be block nodes**: Paragraphs, headings, lists, quotes, code blocks—not text or links.

2. **Inline containers can only contain inline nodes**: Paragraphs, headings, and links can contain text, linebreaks, inline code, and other links.

3. **Lists can only contain list-items**: A list node's children must all be list-item nodes.

4. **Block containers can only contain block nodes**: Quote nodes expect block-level children.

5. **Childless nodes cannot have children**: Text, linebreak, and inline-code nodes must not have children.

## Validate before saving

A typical workflow validates before persisting:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Validation\Validator;

function saveDocument(RichTextDocument $document): void
{
    $validator = new Validator();
    $result = $validator->validateDocument($document);

    if (!$result->isValid()) {
        $messages = [];
        foreach ($result->getErrors() as $error) {
            $messages[] = $error->getMessage() . ' at ' . $error->getPath();
        }
        throw new \InvalidArgumentException(
            "Invalid document:\n" . implode("\n", $messages)
        );
    }

    // Proceed with saving...
    $json = $document->toJson();
    echo "Document saved successfully.\n";
}
```

## Complete example

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Validation\Validator;

// Create a valid document.
$text = new TextNode(1, 'Valid paragraph text');
$paragraph = new ParagraphNode(1, [$text]);
$root = new RootNode(1, [$paragraph]);
$validDocument = new RichTextDocument($root);

// Create an invalid document (text directly under root).
$invalidRoot = new RootNode(1, [new TextNode(1, 'Invalid placement')]);
$invalidDocument = new RichTextDocument($invalidRoot);

$validator = new Validator();

// Validate both.
echo "Valid document:\n";
$result = $validator->validateDocument($validDocument);
echo $result->isValid() ? "  PASSED\n" : "  FAILED\n";

echo "\nInvalid document:\n";
$result = $validator->validateDocument($invalidDocument);
if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo "  " . $error->getMessage() . "\n";
        echo "  at " . $error->getPath() . "\n";
    }
}
```

Output:

```text
Valid document:
  PASSED

Invalid document:
  Inline node "text" not allowed under root; expected block node
  at /root/children/0
```

## What's next

Now that you can validate documents, learn how to convert them to and from HTML in [HTML Conversion](html-conversion.md).
