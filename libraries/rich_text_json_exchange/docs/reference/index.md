# Reference

Complete API documentation for the Rich Text JSON Exchange library.

## Namespace

`OpenSocial\RichTextJson`

## Core

- [Document](document.md) — `RichTextDocument` class for parsing and serializing

## Nodes

- [Nodes](nodes.md) — All node types: `RootNode`, `ParagraphNode`, `HeadingNode`, `ListNode`, `ListItemNode`, `QuoteNode`, `CodeNode`, `TextNode`, `LinebreakNode`, `LinkNode`, `InlineCodeNode`, `UnknownNode`

## Validation

- [Validation](validation.md) — `Validator`, `ValidationResult`, `ValidationError`

## HTML Conversion

- [HTML Renderer](html-renderer.md) — `HtmlRenderer` for converting documents to HTML
- [HTML Importer](html-importer.md) — `HtmlImporter` for converting HTML to documents

## Utilities

- [Bitmasks](bitmasks.md) — `TextFormat`, `TextDetail` value objects
- [Traverser](traverser.md) — `NodeTraverser`, `NodeVisitorInterface`

## Exceptions

- [Exceptions](exceptions.md) — `JsonDecodeException`, `JsonEncodeException`, `InvalidDocumentException`
