# Nodes

All node types in the Rich Text JSON Exchange library.

## NodeInterface

Base interface for all nodes.

```text
OpenSocial\RichTextJson\Node\NodeInterface
```

### Methods

| Method         | Returns                | Description                            |
|----------------|------------------------|----------------------------------------|
| `getType()`    | `string`               | The node type identifier               |
| `getVersion()` | `int`                  | The node version                       |
| `toArray()`    | `array<string, mixed>` | Array representation for serialization |

---

## Block Nodes

### RootNode

The root container of a document. Contains block-level children only.

```text
OpenSocial\RichTextJson\Node\RootNode
```

**Type:** `root`

#### Constructor

```php
public function __construct(
    int $version,
    array $children = [],
    array $unknownFields = [],
    bool $hasChildrenKey = false
)
```

#### Methods

| Method                                           | Returns                | Description                         |
|--------------------------------------------------|------------------------|-------------------------------------|
| `getType()`                                      | `string`               | Returns `"root"`                    |
| `getVersion()`                                   | `int`                  | Node version                        |
| `getChildren()`                                  | `array<NodeInterface>` | Child nodes                         |
| `withChildren(array $children)`                  | `self`                 | New instance with replaced children |
| `appendChild(NodeInterface $child)`              | `self`                 | New instance with child appended    |
| `insertChild(int $index, NodeInterface $child)`  | `self`                 | New instance with child inserted    |
| `removeChild(int $index)`                        | `self`                 | New instance with child removed     |
| `replaceChild(int $index, NodeInterface $child)` | `self`                 | New instance with child replaced    |
| `toArray()`                                      | `array`                | Array representation                |

---

### ParagraphNode

A paragraph containing inline content.

```text
OpenSocial\RichTextJson\Node\ParagraphNode
```

**Type:** `paragraph`

#### Constructor

```php
public function __construct(
    int $version,
    array $children = [],
    array $unknownFields = [],
    bool $hasChildrenKey = false
)
```

#### Methods

| Method                                           | Returns                | Description                         |
|--------------------------------------------------|------------------------|-------------------------------------|
| `getType()`                                      | `string`               | Returns `"paragraph"`               |
| `getVersion()`                                   | `int`                  | Node version                        |
| `getChildren()`                                  | `array<NodeInterface>` | Inline child nodes                  |
| `withChildren(array $children)`                  | `self`                 | New instance with replaced children |
| `appendChild(NodeInterface $child)`              | `self`                 | New instance with child appended    |
| `insertChild(int $index, NodeInterface $child)`  | `self`                 | New instance with child inserted    |
| `removeChild(int $index)`                        | `self`                 | New instance with child removed     |
| `replaceChild(int $index, NodeInterface $child)` | `self`                 | New instance with child replaced    |
| `toArray()`                                      | `array`                | Array representation                |

---

### HeadingNode

A heading with a level (1-6).

```text
OpenSocial\RichTextJson\Node\HeadingNode
```

**Type:** `heading`

#### Constructor

```php
public function __construct(
    int $version,
    int $level,
    array $children = [],
    array $unknownFields = [],
    bool $hasChildrenKey = false,
    bool $hasVersion = true,
    bool $hasLevel = true
)
```

#### Methods

| Method                                           | Returns                | Description                             |
|--------------------------------------------------|------------------------|-----------------------------------------|
| `getType()`                                      | `string`               | Returns `"heading"`                     |
| `getVersion()`                                   | `int`                  | Node version                            |
| `getLevel()`                                     | `int`                  | Heading level (1-6)                     |
| `getChildren()`                                  | `array<NodeInterface>` | Inline child nodes                      |
| `hasVersion()`                                   | `bool`                 | Whether version was explicitly provided |
| `hasLevel()`                                     | `bool`                 | Whether level was explicitly provided   |
| `withLevel(int $level)`                          | `self`                 | New instance with different level       |
| `withChildren(array $children)`                  | `self`                 | New instance with replaced children     |
| `appendChild(NodeInterface $child)`              | `self`                 | New instance with child appended        |
| `insertChild(int $index, NodeInterface $child)`  | `self`                 | New instance with child inserted        |
| `removeChild(int $index)`                        | `self`                 | New instance with child removed         |
| `replaceChild(int $index, NodeInterface $child)` | `self`                 | New instance with child replaced        |
| `toArray()`                                      | `array`                | Array representation                    |

---

### ListNode

A list (bullet or numbered).

```text
OpenSocial\RichTextJson\Node\ListNode
```

**Type:** `list`

#### Constructor

```php
public function __construct(
    int $version,
    string $listType = 'bullet',
    ?int $start = null,
    array $children = [],
    array $unknownFields = [],
    bool $hasChildrenKey = false,
    bool $hasListType = false,
    bool $hasStart = false
)
```

#### Methods

| Method                                           | Returns                | Description                           |
|--------------------------------------------------|------------------------|---------------------------------------|
| `getType()`                                      | `string`               | Returns `"list"`                      |
| `getVersion()`                                   | `int`                  | Node version                          |
| `getListType()`                                  | `string`               | `"bullet"` or `"number"`              |
| `getStart()`                                     | `?int`                 | Start number for numbered lists       |
| `getChildren()`                                  | `array<NodeInterface>` | ListItemNode children                 |
| `withListType(string $listType)`                 | `self`                 | New instance with different list type |
| `withStart(?int $start)`                         | `self`                 | New instance with different start     |
| `withChildren(array $children)`                  | `self`                 | New instance with replaced children   |
| `appendChild(NodeInterface $child)`              | `self`                 | New instance with child appended      |
| `insertChild(int $index, NodeInterface $child)`  | `self`                 | New instance with child inserted      |
| `removeChild(int $index)`                        | `self`                 | New instance with child removed       |
| `replaceChild(int $index, NodeInterface $child)` | `self`                 | New instance with child replaced      |
| `toArray()`                                      | `array`                | Array representation                  |

---

### ListItemNode

An item within a list. Contains block-level children.

```text
OpenSocial\RichTextJson\Node\ListItemNode
```

**Type:** `list-item`

#### Constructor

```php
public function __construct(
    int $version,
    array $children = [],
    array $unknownFields = [],
    bool $hasChildrenKey = false
)
```

#### Methods

| Method                                           | Returns                | Description                         |
|--------------------------------------------------|------------------------|-------------------------------------|
| `getType()`                                      | `string`               | Returns `"list-item"`               |
| `getVersion()`                                   | `int`                  | Node version                        |
| `getChildren()`                                  | `array<NodeInterface>` | Block child nodes                   |
| `withChildren(array $children)`                  | `self`                 | New instance with replaced children |
| `appendChild(NodeInterface $child)`              | `self`                 | New instance with child appended    |
| `insertChild(int $index, NodeInterface $child)`  | `self`                 | New instance with child inserted    |
| `removeChild(int $index)`                        | `self`                 | New instance with child removed     |
| `replaceChild(int $index, NodeInterface $child)` | `self`                 | New instance with child replaced    |
| `toArray()`                                      | `array`                | Array representation                |

---

### QuoteNode

A block quotation. Contains block-level children.

```text
OpenSocial\RichTextJson\Node\QuoteNode
```

**Type:** `quote`

#### Constructor

```php
public function __construct(
    int $version,
    array $children = [],
    array $unknownFields = [],
    bool $hasChildrenKey = false
)
```

#### Methods

| Method                                           | Returns                | Description                         |
|--------------------------------------------------|------------------------|-------------------------------------|
| `getType()`                                      | `string`               | Returns `"quote"`                   |
| `getVersion()`                                   | `int`                  | Node version                        |
| `getChildren()`                                  | `array<NodeInterface>` | Block child nodes                   |
| `withChildren(array $children)`                  | `self`                 | New instance with replaced children |
| `appendChild(NodeInterface $child)`              | `self`                 | New instance with child appended    |
| `insertChild(int $index, NodeInterface $child)`  | `self`                 | New instance with child inserted    |
| `removeChild(int $index)`                        | `self`                 | New instance with child removed     |
| `replaceChild(int $index, NodeInterface $child)` | `self`                 | New instance with child replaced    |
| `toArray()`                                      | `array`                | Array representation                |

---

### CodeNode

A code block.

```text
OpenSocial\RichTextJson\Node\CodeNode
```

**Type:** `code`

#### Constructor

```php
public function __construct(
    int $version,
    string $code,
    ?string $language = null,
    array $unknownFields = [],
    bool $hasVersion = true,
    bool $hasCode = true
)
```

#### Methods

| Method                            | Returns   | Description                             |
|-----------------------------------|-----------|-----------------------------------------|
| `getType()`                       | `string`  | Returns `"code"`                        |
| `getVersion()`                    | `int`     | Node version                            |
| `getCode()`                       | `string`  | The code content                        |
| `getLanguage()`                   | `?string` | Programming language or null            |
| `hasVersion()`                    | `bool`    | Whether version was explicitly provided |
| `hasCode()`                       | `bool`    | Whether code was explicitly provided    |
| `withCode(string $code)`          | `self`    | New instance with different code        |
| `withLanguage(?string $language)` | `self`    | New instance with different language    |
| `toArray()`                       | `array`   | Array representation                    |

---

## Inline Nodes

### TextNode

Plain text with optional formatting.

```text
OpenSocial\RichTextJson\Node\TextNode
```

**Type:** `text`

#### Constructor

```php
public function __construct(
    int $version,
    string $text,
    ?int $format = null,
    ?int $detail = null,
    array $unknownFields = [],
    bool $hasVersion = true,
    bool $hasText = true
)
```

#### Methods

| Method                     | Returns  | Description                             |
|----------------------------|----------|-----------------------------------------|
| `getType()`                | `string` | Returns `"text"`                        |
| `getVersion()`             | `int`    | Node version                            |
| `getText()`                | `string` | The text content                        |
| `getFormat()`              | `?int`   | Format bitmask or null                  |
| `getDetail()`              | `?int`   | Detail bitmask or null                  |
| `hasVersion()`             | `bool`   | Whether version was explicitly provided |
| `hasText()`                | `bool`   | Whether text was explicitly provided    |
| `withText(string $text)`   | `self`   | New instance with different text        |
| `withFormat(?int $format)` | `self`   | New instance with different format      |
| `withDetail(?int $detail)` | `self`   | New instance with different detail      |
| `toArray()`                | `array`  | Array representation                    |

---

### LinebreakNode

A line break within inline content.

```text
OpenSocial\RichTextJson\Node\LinebreakNode
```

**Type:** `linebreak`

#### Constructor

```php
public function __construct(
    int $version,
    array $unknownFields = []
)
```

#### Methods

| Method         | Returns  | Description           |
|----------------|----------|-----------------------|
| `getType()`    | `string` | Returns `"linebreak"` |
| `getVersion()` | `int`    | Node version          |
| `toArray()`    | `array`  | Array representation  |

---

### LinkNode

A hyperlink containing inline children.

```text
OpenSocial\RichTextJson\Node\LinkNode
```

**Type:** `link`

#### Constructor

```php
public function __construct(
    int $version,
    string $url,
    ?string $title = null,
    array $children = [],
    array $unknownFields = [],
    bool $hasChildrenKey = false,
    bool $hasVersion = true,
    bool $hasUrl = true
)
```

#### Methods

| Method                                           | Returns                | Description                             |
|--------------------------------------------------|------------------------|-----------------------------------------|
| `getType()`                                      | `string`               | Returns `"link"`                        |
| `getVersion()`                                   | `int`                  | Node version                            |
| `getUrl()`                                       | `string`               | The link URL                            |
| `getTitle()`                                     | `?string`              | Link title (tooltip) or null            |
| `getChildren()`                                  | `array<NodeInterface>` | Inline child nodes                      |
| `hasVersion()`                                   | `bool`                 | Whether version was explicitly provided |
| `hasUrl()`                                       | `bool`                 | Whether URL was explicitly provided     |
| `withUrl(string $url)`                           | `self`                 | New instance with different URL         |
| `withTitle(?string $title)`                      | `self`                 | New instance with different title       |
| `withChildren(array $children)`                  | `self`                 | New instance with replaced children     |
| `appendChild(NodeInterface $child)`              | `self`                 | New instance with child appended        |
| `insertChild(int $index, NodeInterface $child)`  | `self`                 | New instance with child inserted        |
| `removeChild(int $index)`                        | `self`                 | New instance with child removed         |
| `replaceChild(int $index, NodeInterface $child)` | `self`                 | New instance with child replaced        |
| `toArray()`                                      | `array`                | Array representation                    |

---

### InlineCodeNode

Inline code span.

```text
OpenSocial\RichTextJson\Node\InlineCodeNode
```

**Type:** `inline-code`

#### Constructor

```php
public function __construct(
    int $version,
    string $code,
    ?string $language = null,
    array $unknownFields = [],
    bool $hasVersion = true,
    bool $hasCode = true
)
```

#### Methods

| Method                            | Returns   | Description                             |
|-----------------------------------|-----------|-----------------------------------------|
| `getType()`                       | `string`  | Returns `"inline-code"`                 |
| `getVersion()`                    | `int`     | Node version                            |
| `getCode()`                       | `string`  | The code content                        |
| `getLanguage()`                   | `?string` | Programming language or null            |
| `hasVersion()`                    | `bool`    | Whether version was explicitly provided |
| `hasCode()`                       | `bool`    | Whether code was explicitly provided    |
| `withCode(string $code)`          | `self`    | New instance with different code        |
| `withLanguage(?string $language)` | `self`    | New instance with different language    |
| `toArray()`                       | `array`   | Array representation                    |

---

## Special Nodes

### UnknownNode

Preserves unknown node types for forward compatibility.

```text
OpenSocial\RichTextJson\Node\UnknownNode
```

**Type:** (varies — returns original type string)

#### Constructor

```php
public function __construct(array $data)
```

| Parameter  | Type                   | Description            |
|------------|------------------------|------------------------|
| `$data`    | `array<string, mixed>` | The original node data |

#### Methods

| Method         | Returns  | Description                 |
|----------------|----------|-----------------------------|
| `getType()`    | `string` | The original type string    |
| `getVersion()` | `int`    | The original version        |
| `toArray()`    | `array`  | The original data unchanged |

---

## Child Manipulation Methods

All nodes with children (`RootNode`, `ParagraphNode`, `HeadingNode`, `ListNode`, `ListItemNode`, `QuoteNode`, `LinkNode`) share these methods:

| Method                                           | Description          | Throws                                            |
|--------------------------------------------------|----------------------|---------------------------------------------------|
| `withChildren(array $children)`                  | Replace all children | —                                                 |
| `appendChild(NodeInterface $child)`              | Add child at end     | —                                                 |
| `insertChild(int $index, NodeInterface $child)`  | Insert at position   | `InvalidArgumentException` if index out of bounds |
| `removeChild(int $index)`                        | Remove at position   | `InvalidArgumentException` if index out of bounds |
| `replaceChild(int $index, NodeInterface $child)` | Replace at position  | `InvalidArgumentException` if index out of bounds |

All methods return a new instance (immutable pattern).

## See Also

- [Bitmasks](bitmasks.md) — TextFormat and TextDetail for text styling
- [Document](document.md) — Creating documents from nodes
