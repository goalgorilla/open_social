# Documentation

Welcome to the Rich Text JSON Exchange PHP library documentation.

## Getting Started

If you're new to the library, start with the [Tutorials](tutorials/index.md) to learn the basics through hands-on practice.

## Documentation Sections

### [Tutorials](tutorials/index.md)

Step-by-step guides that teach you how to use the library:

- [Getting Started](tutorials/getting-started.md) — Parse, access, and serialize documents
- [Editing Documents](tutorials/editing-documents.md) — Modify documents using immutable APIs
- [Validating Documents](tutorials/validating-documents.md) — Check structural correctness
- [HTML Conversion](tutorials/html-conversion.md) — Render and import HTML

### [How-To Guides](how-to/index.md)

Task-oriented recipes for specific problems:

- Parsing & Serialization
- Creating & Editing
- Validation
- HTML Conversion
- Tree Traversal
- Forward Compatibility

### [Reference](reference/index.md)

Complete API documentation:

- [Document](reference/document.md) — `RichTextDocument` class
- [Nodes](reference/nodes.md) — All node types
- [Validation](reference/validation.md) — Validator and errors
- [HTML Renderer](reference/html-renderer.md) — HTML output
- [HTML Importer](reference/html-importer.md) — HTML input
- [Bitmasks](reference/bitmasks.md) — Text formatting
- [Traverser](reference/traverser.md) — Tree walking
- [Exceptions](reference/exceptions.md) — Error types

### [Explanation](explanation/index.md)

Background and design concepts:

- [Immutability](explanation/immutability.md) — Why nodes are immutable
- [Forward Compatibility](explanation/forward-compatibility.md) — Preserving unknown content
- [Lossless Round-Tripping](explanation/lossless-round-tripping.md) — JSON preservation guarantees
- [Relationship to Specification](explanation/relationship-to-specification.md) — Library vs. spec scope
- [Security](explanation/security.md) — XSS protection and best practices

## Specification

This library implements the [OpenSocial Rich Text JSON Exchange format](../Open-Social-Rich-Text-JSON-exchange-format.pdf). Consult the specification for normative format details.
