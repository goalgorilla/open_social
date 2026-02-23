# Forward Compatibility

The OpenSocial Rich Text JSON Exchange specification will evolve. New node types will be added, existing nodes may gain new fields, and bitmasks may use additional bits. This library is designed to handle content from future specification versions gracefully, preserving data it doesn't understand so that nothing is lost.

## The problem

Consider this scenario:

1. A new specification version adds a `callout` node type
2. A producer (editor, API) creates documents using this new type
3. A consumer running an older library version receives these documents

Without forward compatibility, the consumer would either:
- Fail to parse the document (breaking)
- Silently discard the unknown node (data loss)

Neither outcome is acceptable in a distributed system where different components upgrade at different times.

## Three types of unknown content

The library preserves three categories of content it doesn't recognize:

### Unknown node types

When the parser encounters a node with an unrecognized type, it wraps the entire node in an `UnknownNode`:

```php
// JSON with future node type
{
  "type": "callout",
  "version": 1,
  "style": "warning",
  "children": [...]
}

// Parsed as UnknownNode containing all original data
```

The `UnknownNode` stores the complete original data and returns it unchanged when serialized:

```php
$node = $root->getChildren()[0];
if ($node instanceof UnknownNode) {
    echo $node->getType();     // "callout"
    echo $node->getVersion();  // 1
    print_r($node->toArray()); // Original data intact
}
```

### Unknown fields

Every known node type preserves unrecognized fields in an `unknownFields` array:

```php
// Paragraph with future field
{
  "type": "paragraph",
  "version": 1,
  "futureField": "some value",
  "children": [...]
}

// The paragraph is parsed normally, but futureField is preserved
```

When you serialize the node, unknown fields appear in the output:

```php
$array = $paragraph->toArray();
// Contains: type, version, children, AND futureField
```

This happens automatically—you don't need to do anything special.

### Unknown bitmask bits

Text formatting uses bitmasks where each bit represents a style:

- Bit 1 (value 1): Bold
- Bit 2 (value 2): Italic
- Bit 4 (value 4): Underline
- Bit 8 (value 8): Strikethrough

Future versions might use bit 16, bit 32, etc. The `TextFormat` and `TextDetail` classes preserve unknown bits when you modify known ones:

```php
// Text with format=17 (bold + unknown bit 16)
$text = new TextNode(1, 'Hello', 17);

// Add italic using TextFormat
$format = TextFormat::fromNullable($text->getFormat());
$newFormat = $format->withItalic(true);
$text = $text->withFormat($newFormat->getValue());

// Result: format=19 (1 + 2 + 16)
// The unknown bit 16 is preserved
```

## Why this matters

### Interoperability

In real systems, different components upgrade independently:

- Mobile apps may lag behind web apps
- Third-party integrations may use older library versions
- Cached content may have been created with newer versions

Forward compatibility ensures documents flow through the system without data loss, regardless of version mismatches.

### Graceful degradation

An older consumer can:

1. Parse the document successfully
2. Display what it understands (known node types)
3. Pass through what it doesn't (unknown nodes preserved)
4. Save the document without losing the unknown content

The user experience degrades gracefully—unknown nodes might not render, but they're not deleted.

### Safe editing

Even when editing documents with unknown content:

```php
// Load document with unknown node types
$doc = RichTextDocument::fromJson($json);

// Add a new paragraph (known type)
$newParagraph = new ParagraphNode(1, [new TextNode(1, 'New content')]);
$newRoot = $doc->getRoot()->appendChild($newParagraph);
$newDoc = new RichTextDocument($newRoot);

// Save—unknown nodes from the original are still there
$output = $newDoc->toJson();
```

The unknown nodes remain untouched because you only modified the parts you understand.

## Design decisions

### Why wrap in UnknownNode?

Alternatives considered:

1. **Skip unknown nodes**: Loses data
2. **Throw an exception**: Breaks parsing
3. **Store as generic array**: Loses type information, harder to traverse

`UnknownNode` provides a typed wrapper that integrates with the rest of the node system while preserving all original data.

### Why store unknown fields per node?

Alternatives considered:

1. **Ignore unknown fields**: Loses data
2. **Store in a central registry**: Complicates serialization, loses field-node association

Storing `unknownFields` on each node keeps the data co-located and makes serialization straightforward.

### Why preserve unknown bits?

The `TextFormat` class could have stripped unknown bits for "cleaner" values. But:

- Unknown bits might be meaningful to newer consumers
- Stripping them silently loses information
- The storage cost is negligible (they're just bits in an integer)

## Limitations

Forward compatibility has limits:

- **Unknown node children**: If an unknown node has children, they're preserved but not parsed into typed nodes. The entire subtree is stored as raw data.

- **Semantic meaning**: The library preserves unknown content syntactically but can't interpret it semantically. Unknown nodes won't render to HTML, for example.

- **Validation**: Unknown nodes pass validation (they're not structural errors), but the library can't validate their internal structure.

## Summary

Forward compatibility is a core design principle:

- **Unknown node types** → `UnknownNode`
- **Unknown fields** → `unknownFields` array on each node
- **Unknown bitmask bits** → preserved in integer value

This ensures that documents created with future specification versions can safely pass through older library versions, enabling gradual upgrades across distributed systems.
