# Relationship to the Specification

This library implements the OpenSocial Rich Text JSON Exchange format. Understanding the boundary between what the specification defines and what this library provides helps you know when to consult which resource.

## What the specification defines

The [OpenSocial Rich Text JSON Exchange specification](../../Open-Social-Rich-Text-JSON-exchange-format.pdf) is the normative reference for the format. It defines:

### Document structure

- The root object must have a `root` property containing a root node
- Nodes have `type` and `version` fields
- The tree structure: root → blocks → inline content

### Node types

- Block nodes: root, paragraph, heading, list, list-item, quote, code
- Inline nodes: text, linebreak, link, inline-code
- Required and optional fields for each type
- Child constraints (what can contain what)

### Field semantics

- What `level` means for headings (1-6)
- What `listType` values are valid (`bullet`, `number`)
- What `format` bits mean (bold, italic, underline, strikethrough)
- What `detail` bits mean (superscript, subscript)

### Extensibility

- How unknown node types should be handled
- How unknown fields should be preserved
- How unknown bitmask bits should be treated

## What this library implements

This PHP library provides:

### Parsing and serialization

- `RichTextDocument::fromJson()` and `fromArray()` parse the format
- `toJson()` and `toArray()` serialize back
- Strongly-typed DTOs for each node type

### Structural validation

- `Validator` checks that the document structure is valid
- Ensures blocks don't appear where inlines are expected (and vice versa)
- Verifies required fields are present

### Forward compatibility

- `UnknownNode` preserves unrecognized node types
- Unknown fields are stored and re-serialized
- Unknown bitmask bits are preserved

### HTML conversion

- `HtmlRenderer` converts documents to safe HTML
- `HtmlImporter` converts HTML back to documents (best-effort)

### Editing support

- Immutable `with*` methods for modifications
- `NodeTraverser` for tree transformations

## What this library does NOT do

### No user interface

The library doesn't provide editors, toolbars, or any UI components. It's a data layer only. You'll need to build or integrate a separate editor that produces and consumes the JSON format.

### No storage or transport

The library doesn't define how to store documents (database, file system) or transport them (HTTP, WebSockets). It gives you JSON; what you do with that JSON is up to you.

### No editing policies

The library doesn't enforce policies like:
- Maximum document size
- Allowed node types (e.g., "no code blocks in comments")
- Required content (e.g., "must have at least one paragraph")

These are application-level concerns. Use validation results and application logic to enforce your policies.

### No content sanitization

The library validates structure but doesn't sanitize content:
- It won't strip profanity from text
- It won't limit URL destinations
- It won't enforce image dimensions

Build these checks into your application as needed.

### No semantic interpretation

The library knows that `format: 3` means "bold and italic" but doesn't interpret what that means for your application. Rendering, indexing, and search are your responsibility.

## When to consult the specification

Read the specification when you need to understand:

- **The exact meaning of a field**: What does `detail: 2` mean? (subscript)
- **Validity of a value**: Is `level: 7` valid for a heading? (no)
- **Extension rules**: How should I handle a node type I don't recognize?
- **Interoperability**: What can I expect other implementations to support?

The specification is the source of truth for format semantics.

## When to consult this documentation

Read this documentation when you need to understand:

- **How to use the PHP API**: What method do I call to parse JSON?
- **What exceptions can be thrown**: When will `fromJson()` fail?
- **How immutability works**: Why do `with*` methods return new instances?
- **How to traverse the tree**: How do I find all links in a document?

This documentation is the source of truth for library usage.

## Version alignment

The library aims to support the current specification version. When the specification adds new node types or fields:

1. A library update will add typed support for the new features
2. Older library versions will preserve the new content via forward compatibility
3. The README and changelog will note specification version alignment

Check the library version and specification version when troubleshooting interoperability issues.

## Practical guidance

### Typical workflow

1. **Consult the spec** to understand what document structure you need
2. **Use the library** to create that structure in PHP
3. **Validate** to ensure your document is structurally correct
4. **Serialize** to JSON for storage or transport
5. **Render to HTML** for display (knowing this is lossy)

### When things don't work

If parsing fails:
- Check the exception message and path
- Consult the spec for valid field types and values
- Verify your JSON is well-formed

If validation fails:
- Read the validation error message
- Consult the spec for structural rules
- Fix the document structure

If HTML looks wrong:
- Check how the renderer maps node types to HTML
- Consult the spec for field semantics (e.g., format bits)
- Remember that unknown nodes don't render

## Summary

| Question                    | Consult                                        |
|-----------------------------|------------------------------------------------|
| What does field X mean?     | Specification                                  |
| What node types exist?      | Specification                                  |
| How do I call method Y?     | Library documentation                          |
| What exceptions can occur?  | Library documentation                          |
| Why is my document invalid? | Both (spec for rules, docs for error handling) |

The specification defines the format. This library implements it in PHP. Together, they give you everything you need to work with Rich Text JSON documents.
