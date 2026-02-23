<!-- 
This is the canonical version of the Open Social Rich Text JSON exchange format,
changes to this document should be documented in the changelog in section 10
and the changes should be published to developer.getopensocial.com after review.
-->
# Open Social Rich Text JSON exchange format (Draft)

## Abstract

This document defines the Rich Text JSON Exchange format, a structured and
versioned data model for representing rich text content as JSON. The format
specifies a hierarchical node-based structure suitable for interoperable
exchange between systems, without prescribing transport mechanisms, rendering
behavior, or editor-specific state. A core set of block and inline node types is
provided, along with rules for extensibility, forward compatibility, and
independent node versioning. This specification is intended solely to define a
portable serialization format for rich text.

## 1. Introduction and Overview

This document defines the Rich Text JSON Exchange format, a structured data
model for representing rich text content as a hierarchy of typed nodes encoded
in JSON. The format provides a consistent and interoperable serialization
mechanism for applications that create, store, or exchange rich text, without
prescribing how that content is rendered or transported.

The data model describes only the logical structure of content. It does not
define presentation rules, styling engines, layout behavior, or editor-specific
runtime state. Likewise, this specification does not address transport concerns
such as HTTP behavior, compression, storage formats, or security protocols. Its
sole purpose is to define a portable, forward-compatible representation of rich
text suitable for interchange between systems.

Each node type in the model is independently versioned, enabling the format to
evolve over time while preserving compatibility between implementations. Unknown
node types, unknown fields, and newer version values are preserved by consumers
to maintain document integrity across system boundaries. A core set of block-
and inline-level node types is defined, and applications may introduce
additional node types as extensions, subject to the structural constraints of
this specification.

The result is a minimal, extensible, and implementation-agnostic interchange
format designed to support diverse rich-text workflows while remaining stable
across application domains and rendering environments.

A non-normative JSON Schema is provided as an external companion artifact to
support automated validation and testing of documents encoded using this format.
The JSON Schema is informational and does not replace the normative requirements
defined in this specification.

### 1.1. Requirements Notation and Conventions

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in this document are to be
interpreted as described in
[RFC2119](https://datatracker.ietf.org/doc/html/rfc2119).

Throughout this document, values are quoted to indicate that they are to be
taken literally. When using these values in protocol messages, the quotes MUST
NOT be used as part of the value.

### 1.2. Definitions

Document
: The top-level container representing a single piece of content.

Node
: A typed element in the document tree. Nodes may contain child nodes.

Block node
: A node that participates in the document structure (paragraph, heading,
list).

Inline node
: A node that appears within the textual flow of a block (text, link, inline
  code).

Producer
: An implementation that serializes a document or node into the Rich Text
  JSON Exchange format. Any system that outputs content in this format acts
  as a producer for that content.

Consumer
: An implementation that parses, interprets, or renders a document or node
  encoded in the Rich Text JSON Exchange format. Any system that reads
  content in this format acts as a consumer for that content.

## 2. Format

The Rich Text JSON Exchange data model defines a hierarchical structure of nodes
rooted in a fixed top-level container. Documents describe their content using
typed nodes, each of which defines its own properties and its own serialization
version. The format is represented using JSON as defined in RFC 8259, including
its rules for objects, arrays, numbers, and Unicode strings. This section
defines how documents and nodes are represented.

### 2.1. Document

A document is represented as a JSON object with a single required property, "
root". The value of "root" MUST be a node whose "type" is the literal string "
root". This node forms the entry point for all content and reserves space for
future expansion at the top level without altering the structure of the content
tree.

The "root" node MUST contain:

* "type": The literal string "root".
* "version": A positive integer indicating the serialization version of the root
  node.
* "children": OPTIONAL. If present, an array of block-level nodes forming the
  body of the document.

Example:

```json
{
  "root": {
    "type": "root",
    "version": 1,
    "children": [
      ...
    ]
  }
}
```

The root node MUST NOT appear as a child of any other node. Only block-level
nodes MAY appear directly within "children" of the root.

### 2.2. Node

Content is represented as nodes. A node is a JSON object that declares its type,
its per-node serialization version, and any additional fields required by that
type.

All nodes MUST contain:

* "type": A string that identifies the node type.\
  Node type names are case-sensitive and MUST correspond to a type defined by
  this specification or an extension.
* "version": A positive integer indicating the serialization version for this
  node type.

Node types MAY define additional fields beyond "type", "version", and "
children". These fields describe the node’s semantics. For example, a paragraph
node MAY define a "format" field that encodes text styling information.

Nodes MAY contain:

* "children": An array of child nodes. The allowed child types depend on whether
  the node is block-level or inline-level and on the node’s own definition.

Nodes MUST NOT define fields that conflict with the required semantics of "
type", "version", or "children".

Consumers MUST preserve unknown node types and unknown fields (including unknown
node-specific version values) to ensure forward compatibility.

### 2.3. Block vs Inline Nodes

Nodes are classified into block-level and inline-level categories according to
their structural role.

Block-level nodes:

* Represent document structure (e.g., paragraphs, headings, lists).
* MAY appear as children of the root or of other block-level nodes, depending on
  the node’s rules.
* MAY contain either block-level nodes or inline-level nodes, as allowed by the
  specific node type definition.

Inline-level nodes:

* Participate in text flow within a block.
* MUST NOT appear directly under the root node.
* MUST NOT contain block-level nodes.
* MAY contain other inline nodes if the node type allows nesting.

Consumers MUST treat nodes that appear outside their permitted structural
context as invalid.

### 2.4. Node-Specific Fields

Beyond the shared structural fields, each node type defines its own allowed
fields. These fields express the semantics, formatting, and behavior associated
with that type.

Node-specific fields:

* MUST be valid JSON primitives, arrays, or objects.
* MUST NOT override structural behavior such as node categorization or allowable
  child types.
* MUST be preserved by consumers even if the node type or field is not
  understood.

Non-normative examples include:

* A paragraph node specifying a "format" integer used as a bitmask for styling.
* A heading node declaring a "level" number.
* A link node specifying a "url" string.

The set of node types and their fields is defined in Section 3. Node definitions
MAY be extended by implementations, provided they do not violate structural
rules defined in this section.

### 2.5. Node Versioning

Each node is versioned independently via its "version" field. The "version"value
describes the serialization shape and semantics for that specific node type.

Producers:

* MUST set "version" explicitly on every node.
* MUST increment the "version" value when making a breaking change to the
  serialized representation or semantics of a node type.

Consumers:

* SHOULD treat unknown or newer "version" values as compatible only to the
  extent they can safely interpret the known subset of fields.
* MUST preserve nodes, including their "version" and unknown fields, even if
  they cannot fully interpret them.
* MAY apply migration or normalization logic based on "type" and "version".

Per-node versioning allows different node types to evolve at different rates
without requiring a synchronized global schema change. Implementations MAY
support multiple versions of the same node type concurrently, as long as they
respect the structural rules defined in this section.

### 2.6. Character Encoding

All textual content in this specification is encoded using JSON strings, which
are defined in terms of Unicode scalar values. Implementations:

* MUST treat all string values as Unicode text;
* MUST NOT emit or store malformed Unicode (such as unpaired surrogate code
  points);
* MUST preserve the exact code point sequence provided by the producer unless
  performing explicit text normalization;
* MAY apply a normalization form internally, but MUST NOT alter text in a way
  that changes its meaning or representation unintentionally.

No specific Unicode normalization form is required by this specification;
applications MAY impose additional constraints as appropriate for their domain.

### 2.7. Content Profiles

Some applications MAY restrict the set of node types, fields, or structures
permitted in a given context. Such restrictions constitute a content profile.

A content profile does not change this specification; instead, it defines a
subset of the Rich Text JSON Exchange format that is valid for a particular
application surface, such as a comment field, title field, or short description.

A content profile:

* MAY restrict the allowed node types.
* MAY restrict the allowed fields or values within those node types (e.g. no
  strikethrough text).
* MAY impose additional structural rules (e.g., no nested lists).
* MUST NOT redefine the meaning or structure of any node type defined by this
  specification.

#### 2.7.1. Producer Requirements

When operating under a content profile, producers:

* MUST NOT emit nodes or fields disallowed by that profile.
* SHOULD validate content against the profile before emission.
* MAY normalize content into a profile-compliant form (for example, converting a
  `heading` node into a `paragraph` node), provided the resulting document
  remains valid under this specification.

#### 2.7.2. Consumer Requirements

When operating under a content profile, consumers:

* MAY reject documents that do not conform to the profile.
* MAY sanitize or transform documents to enforce the profile.
* MUST ensure that any accepted or transformed document remains valid according
  to this specification.

A consumer that understands a node type MAY still reject or downgrade it if the
node is disallowed by the active profile.\
This behavior is distinct from handling unknown or unsupported node types.

## 3. Standard Node Types

This section defines the standard node types that conforming implementations
SHOULD support.

Each node definition describes its category (block or inline), required and
optional fields, and the allowed structure of its children.

Historical versions of each node type, if any, are documented in Appendix A.

### 3.1. Root

The `root` node is the entry point of every document and appears only at the top
level.

Current Version: **1**

A `root` node:

* MUST have `"type": "root"`.
* MUST have `"version": 1`.
* MAY include a `"children"` field containing zero or more block-level nodes.

The `root` node MUST NOT appear as the child of any other node.\
Only block-level nodes defined by this specification or extensions MAY appear
in "children".

Example (non-normative):

```json
{
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
}
```

### 3.2. Block Nodes

Block nodes represent the structural elements of a document. They define
layout-level groupings such as paragraphs, headings, lists, quotations, and code
blocks. Block nodes form the primary hierarchy of the document and determine
where inline content may appear.

* Block nodes MAY appear as children of `root` or other block nodes.
* Block nodes MAY contain block or inline nodes depending on node type
  definition.
* Block nodes MUST NOT appear inside inline contexts.

#### 3.2.1. Paragraph

The `paragraph` node represents a block of prose text.

Current Version: **1**

A `paragraph` node:

* MUST have `"type": "paragraph"`
* MUST have `"version": 1`
* MAY include a `"children"` field containing zero or more inline nodes.
* MUST NOT contain block nodes.

Example (non-normative):

```json
{
  "type": "paragraph",
  "version": 1,
  "children": [
    {
      "type": "text",
      "version": 1,
      "text": "This is a paragraph."
    }
  ]
}
```

#### 3.2.2. Heading

The `heading` node represents a section heading.

Current Version: **1**

A `heading` node:

* MUST have `"type": "heading"`.
* MUST have `"version": 1`.
* MUST include a `"level"` field whose value is an integer indicating
  hierarchy (RECOMMENDED: 1–6).
* MAY include `"children"` containing zero or more inline nodes.
* MUST NOT contain block nodes.

Example (non-normative):

```json
{
  "type": "heading",
  "version": 1,
  "level": 2,
  "children": [
    {
      "type": "text",
      "version": 1,
      "text": "Section title"
    }
  ]
}
```

#### 3.2.3. List and List Item

The `list` and `list-item` nodes represent ordered, and unordered lists.

##### 3.2.3.1. List

Current Version: **1**

A `list` node:

* MUST have `"type": "list"`.
* MUST have `"version": 1`.
* MAY include `"listType"` with one of: `"bullet"` , `"number"`
  * In case this value is omitted `"bullet"` will be used.
* MAY include `"children"` containing zero or more `list-item` nodes.
* When `"listType"` is `"number"`
  * MAY include `"start"` as integer value
    * In case this value is omitted `1` will be used.

Example (non-normative):

```json
{
  "type": "list",
  "version": 1,
  "listType": "number",
  "start": 3,
  "children": [
    ...
    list
    items
    ...
  ]
}
```

##### 3.2.3.2. List Item

A `list-item` node represents a single list entry.

Current Version: **1**

A `list-item` node:

* MUST have `"type": "list-item"`.
* MUST have `"version": 1`.
* MAY include `"children"` containing zero or more nodes (typically a paragraph,
  text, or optionally nested lists).

Nested lists are represented by placing a `list` node within the "children" of a
`list-item`.

Example (non-normative):

```json
{
  "type": "list-item",
  "version": 1,
  "children": [
    {
      "type": "text",
      "version": 1,
      "text": "First item"
    }
  ]
}
```

#### 3.2.4. Quote

The `quote` node represents a block quotation.

Current Version: **1**

A `quote` node:

* MUST have `"type": "quote"`.
* MUST have `"version": 1`.
* MAY include `"children"` containing zero or more block nodes.
* MUST NOT contain inline nodes directly.

Example (non-normative):

```json
{
  "type": "quote",
  "version": 1,
  "children": [
    {
      "type": "paragraph",
      "version": 1,
      "children": [
        {
          "type": "text",
          "version": 1,
          "text": "Quoted text."
        }
      ]
    }
  ]
}
```

#### 3.2.5. Code

The `code` node represents a block of preformatted code.

Current Version: **1**

A `code` node:

* MUST have `"type": "code"`.
* MUST have `"version": 1`.
* MUST include a `"code"` field containing a string with the full code content
* MAY include `"language"` field containing a string identifying the programming
  language. (e.g., "js", "python").

The content of a `code` node MUST be treated as preformatted and MUST NOT be
reflowed as normal prose.

Example (non-normative):

```json
{
  "type": "code",
  "version": 1,
  "language": "js",
  "code": "console.log('Hello');\n"
}
```

### 3.3. Inline Nodes

Inline nodes represent content that participates in the text flow of a block.
They appear within paragraphs, headings, and other inline-capable containers,
and correspond to text spans, line breaks, links, and inline code fragments.

* Inline nodes MAY appear only inside block nodes or inline nodes.
* Inline nodes MUST NOT contain block-level children.
* Inline nodes define the smallest addressable units of text content and its
  immediate annotations.

#### 3.3.1. Text

The `text` node represents an atomic inline text span. Formatting is encoded
directly on the node.

Current Version: **1**

A `text` node:

* MUST have `"type": "text"`.
* MUST have `"version": 1`.
* MUST include a `"text"` field containing the raw string content.
* MAY include a `"format"` field containing an integer bitmask for styling.
* MAY include a `"detail"` field containing an integer bitmask for extended
  attributes.
* MUST NOT include "children"

The specific bit assignments for "format" and "detail" are defined elsewhere in
this specification or by profile. Non-normative examples of flags include bold,
italic, underline, strikethrough, superscript, and subscript.

Example (non-normative):

```json
{
  "type": "text",
  "version": 1,
  "text": "Hello",
  "format": 3
}
```

#### 3.3.2. Linebreak

The `linebreak` node represents a soft line break within a block of text (
equivalent to a `<br>` in HTML).

Current Version: **1**

A `linebreak` node:

* MUST have `"type": "linebreak"`.
* MUST have `"version": 1`.
* MUST NOT include `"children"`.

Example (non-normative):

```json
{
  "type": "linebreak",
  "version": 1
}
```

#### 3.3.3. Link

The `link` node represents a hyperlink applied to a range of inline content.

Current Version: **1**

A `link` node:

* MUST have `"type": "link"`.
* MUST have `"version": 1`.
* MUST include `"url"` with the target URL.
* MAY include a `"title"` field containing a string providing advisory
  information (e.g., tooltip text).
* MAY include `"children"` containing zero or more inline nodes.
* MUST NOT contain block-level nodes.

Example (non-normative):

```json
{
  "type": "link",
  "version": 1,
  "url": "https://example.com",
  "children": [
    {
      "type": "text",
      "version": 1,
      "text": "Example"
    }
  ]
}
```

#### 3.3.4. Inline Code

The `inline-code` node represents an inline code fragment within a block of
text.

Current Version: **1**

An `inline-code` node:

* MUST have `"type": "inline-code"`.
* MUST have `"version": 1`.
* MUST include a `"code"` field containing a string with the full code content
* MAY include `"language"` field containing a string identifying the programming
  language. (e.g., "js", "python").
* MUST NOT include `"children"`.

Example (non-normative):

```json
{
  "type": "inline-code",
  "version": 1,
  "code": "let x = 1;"
}
```

## 4. Text Formatting Flags

Inline text formatting is represented using integer bitmasks. These bitmasks
appear in the `"format"` and `"detail"` fields of the `text` node and MAY appear
in other inline nodes that support formatting.

### 4.1. Format Bitmask

The `"format"` field encodes styling applied to a text span.\
Each bit corresponds to a specific formatting attribute. Multiple bits MAY be
combined using bitwise OR.

The following bit assignments are defined by this specification:

| Bit | Name          | Meaning                         |
|-----|---------------|---------------------------------|
| 1   | bold          | Text is bold.                   |
| 2   | italic        | Text is italicized.             |
| 4   | underline     | Text is underlined.             |
| 8   | strikethrough | Text has a strike-through line. |

Producers:

* MUST set only the bits defined by this specification
* MUST NOT set any bit positions that are not currently defined; such bits are
  reserved for future versions of this specification.
* MUST preserve unknown bits when transforming or reserializing content

Consumers:

* MUST interpret only the bit positions defined by this specification
* MUST ignore unknown bits when rendering or processing content.
* MUST preserve unknown bits when reserializing the document to avoid losing
  information from future versions of the specification.

### 4.2 Detail Bitmask

The `"detail"` bitmask provides additional inline text attributes that cannot be
represented by `"format"`.

The following bits are defined:

| Bit | Name        | Meaning                              |
|-----|-------------|--------------------------------------|
| 1   | superscript | Text is elevated above the baseline. |
| 2   | subscript   | Text is lowered below the baseline.  |

Producers:

* MUST set only the bits defined by this specification
* MUST NOT set any bit positions that are not currently defined; such bits are
  reserved for future versions of this specification.
* MUST preserve unknown bits when transforming or reserializing content

Consumers:

* MUST interpret only the bit positions defined by this specification
* MUST ignore unknown bits when rendering or processing content.
* MUST preserve unknown bits when reserializing the document to avoid losing
  information from future versions of the specification.

## 5. Extensibility

This section is reserved to document how application specific node types may be
provided within a document.

## 6. Versioning

This section provides additional detail on how node versioning is intended to
function over time. It complements the normative rules defined in Section 2.5
and describes how node type versions evolve, how multiple versions may coexist,
and how implementations can manage changes safely.

### 6.1 Purpose of Node Versioning

Node versioning allows the Rich Text JSON Exchange format to evolve
incrementally.\
Each node type has its own independent version number, enabling:

* changes to one node type without altering others;
* compatibility between documents using different versions of the same node
  type;
* gradual adoption of new serialization formats;
* long-term stability for systems that exchange documents across release cycles.

Version numbers describe *serialization shape and semantics*.\
They do **not** identify application behavior or editor features.

### 6.2 Version Scope

Node version numbers apply only to the node type in which they appear.\
A document MAY contain nodes of multiple versions across different node types.\
A document MAY also contain multiple versions of the same node type.

Version numbers are **local**, not global.

For example:

* A `paragraph` node may be at version `1`,
* while a `heading` node in the same document may be at version `3`.

Node versioning is not tied to:

* the version of the document itself,
* the version of this specification,
* the version of any application.

### 6.3 Evolution of Node Versions

A node type’s version MUST be incremented only when a breaking change is
introduced.\
Breaking changes include:

* altering required fields,
* changing field semantics,
* removing or renaming fields,
* modifying structural rules (e.g., allowed children),
* changing the meaning of bit fields or enumerations.

Non-breaking changes do **not** require a version increment.\
Examples include:

* adding optional fields,
* clarifying semantics,
* tightening validation rules that do not affect existing documents.

This allows implementations to evolve conservatively and predictably.

### 6.4 Coexistence of Multiple Versions

Implementations MAY support multiple versions of a node type simultaneously.\
This is often necessary when:

* documents were created at different times,
* different systems in an ecosystem upgrade at different rates,
* migration is performed lazily or on-demand.

Consumers must follow Section 2.5:

* interpret what they understand,
* preserve what they do not,
* attempt normalization or migration only when safe.

### 6.5 Migration Between Versions

Node migrations fall into two categories:

#### 6.5.1 Upward Migration (Older → Newer)

Upward migration is OPTIONAL.\
Producers MAY upgrade nodes to the latest version, either:

* eagerly (upon parsing),
* lazily (upon saving), or
* never (pass-through behavior).

Upward migration MUST NOT remove information present in the source node.

Examples include:

* adding newly required fields with safe defaults,
* promoting deprecated structures into newer equivalents.

#### 6.5.2 Downward Migration (Newer → Older)

Downward migration is OPTIONAL and MAY be lossy.\
Lossy transformations are permitted only if the resulting node remains valid and
structurally sound.

Applications deciding to downgrade must do so explicitly and with the
understanding that some semantics may be lost.

#### 6.5.3 Migration Safety Requirements

All migrations:

* MUST preserve unknown fields unless the version change explicitly deprecates
  them;
* MUST NOT introduce structures invalid under this specification;
* MUST NOT generate a `version` number inconsistent with the resulting node’s
  shape.

### 6.6 Deprecation and Obsolescence of Versions

This specification MAY deprecate a node version when a successor exists.\
Deprecation does not invalidate existing documents; it signals that producers
SHOULD emit newer versions.

A version MAY become obsolete in future editions of the specification.\
Consumers MAY continue supporting obsolete versions indefinitely but are not
required to do so.

Obsolescence signals that:

* consumers MAY reject the obsolete version,
* producers MUST NOT emit obsolete versions,
* migrations SHOULD be applied where possible.

This lifecycle allows gradual, non-disruptive transitions.

### 6.7 Recording Version History

Section 3 defines the current canonical version of each node type and describes
its required fields and semantics.

Appendix A records all historical versions of each node type, including the
differences between versions and any migration considerations. Appendix A does
not redefine the current version; it documents prior versions for compatibility
and reference.

Extensions MUST NOT define new versions of standard node types.

### 6.8 Unknown Versions

Unknown versions MUST be treated as future-compatible.\
Consumers MUST:

* preserve the node and all unknown fields;
* process the portions they understand;
* avoid failing when encountering unknown elements.

This ensures that documents produced by newer systems remain usable by older
ones.

## 7. Security Considerations

This specification defines a structured data format for representing rich text.
It does not define transport semantics, authentication, authorization, or
encryption. Applications using the Rich Text JSON Exchange format MUST therefore
consider the following security risks associated with parsing, rendering, and
transforming content.

### 7.1. Untrusted Input

Consumers MUST treat all incoming documents as untrusted.\
Malicious producers may construct documents that:

* contain extremely deep or wide node trees in an attempt to exhaust stack or
  heap resources;
* contain repeated or cyclic structures if the consumer’s internal model permits
  references;
* include very large text values or code blocks intended to trigger memory
  exhaustion or denial-of-service conditions.

Consumers SHOULD enforce reasonable limits on:

* maximum depth of the node tree;
* maximum number of nodes;
* maximum length of text fields and attribute values.

### 7.2. Script Injection and Active Content

Since the format allows textual content and hyperlinks, consumers that embed or
render the content in environments capable of executing scripts (e.g., HTML,
Markdown, or UI frameworks) MUST ensure that:

* text nodes are escaped appropriately for the target environment;
* hyperlink URLs are validated or sanitized to prevent URL-based attacks (e.g.,
  `javascript:` URLs);
* inline or block code content is treated as inert unless the application
  explicitly intends to execute it.

This specification does not permit executable content, but downstream rendering
environments may inadvertently introduce it.

### 7.3. Sanitization and Content Profiles

Applications frequently operate under restricted content profiles (Section
2.7).\
Sanitization of disallowed nodes or fields:

* MUST produce structurally valid documents;
* MUST NOT introduce new semantics unintended by the application;
* SHOULD remove or neutralize dangerous content (e.g., untrusted URLs).

Failure to apply proper sanitization may allow:

* privilege escalation through rich-text spoofing,
* unexpected formatting effects,
* embedding of unsafe external resources.

### 7.4. Unicode and Text Processing

Unicode handling may introduce security risks, including:

* visually confusable characters,
* mixed-direction text that can reorder surrounding content,
* combining marks that alter displayed text in deceptive ways.

Consumers SHOULD apply mitigation strategies appropriate to their rendering
environment and application domain.

## 8. Examples

This section provides illustrative examples of valid and invalid documents
encoded in the Rich Text JSON Exchange format. These examples are non-normative
and are intended to aid implementers in understanding typical usage, nesting
patterns, and error conditions.

### 8.1. Simple Document

The following document contains a root node with a paragraph, inline text, and a
link.

```json
{
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
            "text": "Hello "
          },
          {
            "type": "link",
            "version": 1,
            "url": "https://example.com",
            "children": [
              {
                "type": "text",
                "version": 1,
                "text": "world"
              }
            ]
          },
          {
            "type": "text",
            "version": 1,
            "text": "!"
          }
        ]
      }
    ]
  }
}
```

This document demonstrates:

* the required root container;
* basic inline content within a paragraph;
* the use of a link node containing inline children.

### 8.2. Document with Custom Nodes

This section is reserved to document how application specific node types may be
provided within a document.

### 8.3. Invalid Examples

The following examples illustrate documents that violate structural or semantic
rules.

#### 8.3.1. Inline Node at the Document Root

```json
{
  "root": {
    "type": "root",
    "version": 1,
    "children": [
      {
        "type": "text",
        "version": 1,
        "text": "This is invalid."
      }
    ]
  }
}
```

This is invalid because only block nodes may appear as children of the root.

#### 8.3.2. Block Node inside an Inline Node

```json
{
  "root": {
    "type": "root",
    "version": 1,
    "children": [
      {
        "type": "paragraph",
        "version": 1,
        "children": [
          {
            "type": "link",
            "version": 1,
            "url": "#",
            "children": [
              {
                "type": "heading",
                "version": 1,
                "level": 2
              }
            ]
          }
        ]
      }
    ]
  }
}
```

This is invalid because inline nodes (such as links) MUST NOT contain block
nodes.

#### 8.3.3. Missing Required Fields

```json
{
  "root": {
    "type": "root",
    "children": []
  }
}
```

This is invalid because every node MUST include a `"version"` field.

## 9. References

### 9.1. Normative References

The following references are indispensable for the correct interpretation and
implementation of this specification.

\[[RFC2119](https://datatracker.ietf.org/doc/html/rfc2119)]\
Bradner, S., “Key words for use in RFCs to Indicate Requirement Levels”, BCP 14,
RFC 2119, March 1997.

\[[RFC8259](https://datatracker.ietf.org/doc/html/rfc8259)]\
Bray, T., “The JavaScript Object Notation (JSON) Data Interchange Format”, RFC
8259, December 2017.

### 9.2. Informative References

The following reference is provided for informational purposes and does not
define normative requirements of this specification.

\[[JSON-SCHEMA](https://developer.getopensocial.com/schemas/rich-text-json-exchange.schema.json)]\
Rich Text JSON Exchange Format — JSON Schema (Non-Normative),
<https://developer.getopensocial.com/schemas/rich-text-json-exchange.schema.json>

## 10. Changelog

Below is a list of changes made to this specification.

| Date        | Change        |
|-------------|---------------|
| 01 Dec 2025 | Initial draft |

## Appendix A. Node Version History (Informative)

This appendix documents the version history of each node type defined in Section
3.\
Each subsection lists all versions of the node type other than the current
canonical version, which is defined in Section 3.

### A.1 Root

*No historical versions.*

### A.2 Block Nodes

#### A.2.1 Paragraph

*No historical versions.*

#### A.2.2 Heading

*No historical versions.*

#### A.2.3 List

*No historical versions.*

#### A.2.4 List Item

*No historical versions.*

#### A.2.5 Quote

*No historical versions.*

#### A.2.6 Code

*No historical versions.*

### A.3 Inline Nodes

#### A.3.1 Text

*No historical versions.*

#### A.3.2 Linebreak

*No historical versions.*

#### A.3.3 Link

*No historical versions.*

#### A.3.4 Inline Code

*No historical versions.*
