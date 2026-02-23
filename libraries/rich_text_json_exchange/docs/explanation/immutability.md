# Immutability

All nodes in this library are immutable. Once created, a node cannot be changed. When you want to modify a node, you create a new one with the desired changes. This document explains why this design was chosen and what it means for you.

## What immutability means here

Every node class uses PHP 8's `readonly` properties and provides `with*` methods for modifications:

```php
$text = new TextNode(1, 'Hello');
$modified = $text->withText('Goodbye');

// $text still contains "Hello"
// $modified contains "Goodbye"
```

The original node is never changed. The `with*` method returns a completely new instance.

## Why immutability?

### Predictability

When you pass a node to a function, you know that function cannot change your node. The node you have after the call is identical to the node you had before. This eliminates a whole class of bugs where shared mutable state leads to unexpected changes.

```php
function processDocument(RichTextDocument $doc): void {
    // This function cannot modify $doc's internal nodes.
    // It can only create new nodes based on them.
}

$doc = RichTextDocument::fromJson($json);
processDocument($doc);
// $doc is guaranteed unchanged
```

### No side effects

Immutable operations have no side effects. Each `with*` call is a pure function: given the same input, it always produces the same output and changes nothing else. This makes code easier to reason about and test.

### Easy diffing

Because nodes don't change, you can compare "before" and "after" states by simply comparing object references:

```php
$before = $paragraph->getChildren()[0];
$paragraph = $paragraph->replaceChild(0, $newText);
$after = $paragraph->getChildren()[0];

if ($before !== $after) {
    echo "Child was replaced";
}
```

With mutable nodes, you'd need to deep-compare values or track changes manually.

### Safe sharing

You can safely store references to nodes without worrying about them being modified elsewhere. This is particularly useful when implementing undo/redo, caching, or passing nodes between components.

## How `with*` methods work

Each `with*` method creates a new instance with the specified change:

```php
// TextNode::withText() creates a new TextNode
public function withText(string $text): self {
    return new self(
        $this->version,
        $text,                    // New value
        $this->format,            // Preserved
        $this->detail,            // Preserved
        $this->unknownFields,     // Preserved
        $this->hasVersion,
        true,
    );
}
```

Notice that:
- The new value is applied
- All other properties are copied from the original
- Unknown fields are preserved (important for round-tripping)

## Rebuilding nested structures

The trade-off of immutability is that modifying deeply nested content requires rebuilding the path from the changed node up to the root:

```php
// To change text inside a paragraph inside a document:
$root = $document->getRoot();
$paragraph = $root->getChildren()[0];
$text = $paragraph->getChildren()[0];

// Modify the text
$newText = $text->withText('New content');

// Rebuild upward
$newParagraph = $paragraph->replaceChild(0, $newText);
$newRoot = $root->replaceChild(0, $newParagraph);
$newDocument = new RichTextDocument($newRoot);
```

This is more verbose than mutating in place, but the explicit rebuilding makes it clear exactly what changed.

For complex transformations, use the `NodeTraverser` to handle the rebuilding automatically:

```php
$traverser = new NodeTraverser([new MyVisitor()]);
$newRoot = $traverser->traverse($document->getRoot());
```

## Memory considerations

Creating new objects for every change does allocate more memory than mutation. However:

- PHP's copy-on-write semantics mean arrays aren't duplicated until actually modified
- Modern PHP has efficient object allocation
- Short-lived intermediate objects are quickly garbage collected
- The clarity and safety benefits usually outweigh the small overhead

For typical document sizes (hundreds to thousands of nodes), the memory overhead is negligible.

## Comparison to mutable approaches

A mutable design would look like:

```php
// Hypothetical mutable API (NOT how this library works)
$text->setText('New value');  // Modifies in place
```

This is more concise but has drawbacks:

- Harder to track what changed
- Shared references can lead to unexpected modifications
- Difficult to implement undo/redo
- Not safe for concurrent access
- Harder to test (need to reset state between tests)

The immutable approach trades verbosity for safety and predictability—a trade-off that generally favors correctness in complex applications.

## Summary

Immutability is a deliberate design choice that prioritizes:

- **Correctness** over convenience
- **Predictability** over brevity
- **Safety** over performance micro-optimizations

When working with this library, embrace the `with*` pattern. Create new nodes instead of trying to modify existing ones. The result is code that's easier to understand, test, and maintain.
