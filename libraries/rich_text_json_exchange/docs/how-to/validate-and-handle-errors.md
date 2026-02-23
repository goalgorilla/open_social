# Validate and Handle Errors

How to validate documents and respond to validation errors.

## Use ValidatedDocument (recommended)

The simplest approach combines parsing and validation in one step:

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Exception\ValidationException;

try {
    $validated = ValidatedDocument::fromJson($json);
    // Document is guaranteed valid - use it safely
    $root = $validated->getRoot();
} catch (ValidationException $e) {
    // Handle all validation errors
    foreach ($e->getErrors() as $error) {
        echo $error->getMessage() . ' at ' . $error->getPath() . "\n";
    }
}
```

Use `ValidatedDocument` when you need a valid document or nothing.

## Run validation manually

For more control, parse first and validate separately:

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Validation\Validator;

$document = RichTextDocument::fromJson($json);
$validator = new Validator();

$result = $validator->validateDocument($document);
```

## Check if valid

```php
if ($result->isValid()) {
    // Document passes all structural rules.
}
```

## Get all errors

```php
if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo $error->getMessage() . "\n";
        echo "  at: " . $error->getPath() . "\n";
    }
}
```

## Format errors for logging

```php
function formatValidationErrors(ValidationResult $result): string
{
    if ($result->isValid()) {
        return 'Document is valid';
    }

    $lines = ['Validation failed:'];
    foreach ($result->getErrors() as $error) {
        $lines[] = sprintf(
            '  - %s (at %s)',
            $error->getMessage(),
            $error->getPath()
        );
    }
    return implode("\n", $lines);
}

error_log(formatValidationErrors($result));
```

## Throw on invalid

Use `ValidatedDocument` which throws `ValidationException` automatically:

```php
use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Exception\ValidationException;

// Throws ValidationException if invalid
$validated = ValidatedDocument::fromDocument($document);
```

Or manually with custom exception:

```php
use OpenSocial\RichTextJson\Validation\Validator;

function requireValid(RichTextDocument $document): void
{
    $result = (new Validator())->validateDocument($document);

    if (!$result->isValid()) {
        $messages = array_map(
            fn($e) => $e->getMessage() . ' at ' . $e->getPath(),
            $result->getErrors()
        );
        throw new \InvalidArgumentException(
            "Invalid document:\n" . implode("\n", $messages)
        );
    }
}
```

## Common errors and fixes

### Inline node under root

**Error:** `Inline node "text" not allowed under root; expected block node`

**Cause:** Text, link, or inline-code directly under root.

**Fix:** Wrap inline content in a paragraph:

```php
// Wrong
$root = new RootNode(1, [new TextNode(1, 'Hello')]);

// Correct
$root = new RootNode(1, [
    new ParagraphNode(1, [new TextNode(1, 'Hello')]),
]);
```

### Block node in inline context

**Error:** `Block node "paragraph" not allowed in inline context; expected inline node`

**Cause:** Paragraph or other block inside a paragraph, heading, or link.

**Fix:** Only use inline nodes (text, linebreak, link, inline-code) inside paragraphs:

```php
// Wrong
$paragraph = new ParagraphNode(1, [
    new ParagraphNode(1, [...]),  // Block in inline context
]);

// Correct
$paragraph = new ParagraphNode(1, [
    new TextNode(1, 'Content'),
]);
```

### Non-list-item in list

**Error:** `List can only contain list-item nodes, found "paragraph"`

**Cause:** Direct paragraph or other node under list.

**Fix:** Wrap content in list-item nodes:

```php
// Wrong
$list = new ListNode(1, 'bullet', null, [
    new ParagraphNode(1, [...]),
]);

// Correct
$list = new ListNode(1, 'bullet', null, [
    new ListItemNode(1, [
        new ParagraphNode(1, [...]),
    ]),
]);
```

### Missing required fields

**Error:** `Text node missing required "text" field`

**Cause:** Node created without required data.

**Fix:** Ensure all required fields are provided when creating nodes.

## Decide: reject or repair

**Reject** invalid documents when:
- Accepting user-submitted content that must be well-formed
- Storing documents for later rendering
- Enforcing strict contracts

**Attempt repair** when:
- Importing from legacy systems
- Migrating data where some loss is acceptable
- The source is partially trusted

Example repair (wrap orphan inlines):

```php
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\NodeInterface;

function repairOrphanInlines(RootNode $root): RootNode
{
    $newChildren = [];
    $pendingInlines = [];

    foreach ($root->getChildren() as $child) {
        if (isInlineNode($child)) {
            $pendingInlines[] = $child;
        } else {
            if ($pendingInlines !== []) {
                $newChildren[] = new ParagraphNode(1, $pendingInlines);
                $pendingInlines = [];
            }
            $newChildren[] = $child;
        }
    }

    if ($pendingInlines !== []) {
        $newChildren[] = new ParagraphNode(1, $pendingInlines);
    }

    return $root->withChildren($newChildren);
}

function isInlineNode(NodeInterface $node): bool
{
    return in_array($node->getType(), ['text', 'link', 'linebreak', 'inline-code'], true);
}
```

## See also

- [Reference: ValidatedDocument](../reference/document.md#validateddocument) — ValidatedDocument API
- [Reference: Validation](../reference/validation.md) — Complete validation API
- [Reference: ValidationException](../reference/exceptions.md#validationexception) — Exception details
- [Explanation: Relationship to Specification](../explanation/relationship-to-specification.md) — What the library validates
