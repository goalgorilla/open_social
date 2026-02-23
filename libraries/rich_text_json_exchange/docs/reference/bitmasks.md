# Bitmasks

Value objects for text formatting bitmasks.

## TextFormat

Manages the format bitmask for text styling (bold, italic, underline, strikethrough).

```text
OpenSocial\RichTextJson\Bitmask\TextFormat
```

### Bit Values

| Bit  | Value  | Style         |
|------|--------|---------------|
| 1    | `1`    | Bold          |
| 2    | `2`    | Italic        |
| 3    | `4`    | Underline     |
| 4    | `8`    | Strikethrough |

Combined example: bold + italic = `1 + 2 = 3`

### Constructor

```php
public function __construct(int $value)
```

| Parameter  | Type  | Description       |
|------------|-------|-------------------|
| `$value`   | `int` | The bitmask value |

### Static Methods

#### fromNullable

Creates a TextFormat from a nullable integer.

```php
public static function fromNullable(?int $value): ?self
```

**Returns:** `?TextFormat` — null if input is null

#### none

Creates a TextFormat with no formatting (value 0).

```php
public static function none(): self
```

**Returns:** `TextFormat`

### Instance Methods

#### getValue

Gets the raw bitmask value.

```php
public function getValue(): int
```

**Returns:** `int`

#### Query Methods

| Method              | Returns   | Description                      |
|---------------------|-----------|----------------------------------|
| `isBold()`          | `bool`    | True if bold bit is set          |
| `isItalic()`        | `bool`    | True if italic bit is set        |
| `isUnderline()`     | `bool`    | True if underline bit is set     |
| `isStrikethrough()` | `bool`    | True if strikethrough bit is set |

#### Mutator Methods

All return a new `TextFormat` instance:

| Method                             | Parameter                    | Description  |
|------------------------------------|------------------------------|--------------|
| `withBold(bool $enabled)`          | Enable/disable bold          |              |
| `withItalic(bool $enabled)`        | Enable/disable italic        |              |
| `withUnderline(bool $enabled)`     | Enable/disable underline     |              |
| `withStrikethrough(bool $enabled)` | Enable/disable strikethrough |              |

### Unknown Bit Preservation

Unknown bits (e.g., bit 16 from a future spec version) are preserved when modifying known bits:

```php
$format = new TextFormat(17);  // bit 1 (bold) + bit 16 (unknown)
$modified = $format->withItalic(true);
echo $modified->getValue();  // 19 (1 + 2 + 16)
```

---

## TextDetail

Manages the detail bitmask for text details (superscript, subscript).

```text
OpenSocial\RichTextJson\Bitmask\TextDetail
```

### Bit Values

| Bit | Value | Detail |
|-----|-------|--------|
| 1 | `1` | Superscript |
| 2 | `2` | Subscript |

### Constructor

```php
public function __construct(int $value)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | `int` | The bitmask value |

### Static Methods

#### fromNullable

Creates a TextDetail from a nullable integer.

```php
public static function fromNullable(?int $value): ?self
```

**Returns:** `?TextDetail` — null if input is null

#### none

Creates a TextDetail with no details (value 0).

```php
public static function none(): self
```

**Returns:** `TextDetail`

### Instance Methods

#### getValue

Gets the raw bitmask value.

```php
public function getValue(): int
```

**Returns:** `int`

#### Query Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `isSuperscript()` | `bool` | True if superscript bit is set |
| `isSubscript()` | `bool` | True if subscript bit is set |

#### Mutator Methods

All return a new `TextDetail` instance:

| Method | Parameter | Description |
|--------|-----------|-------------|
| `withSuperscript(bool $enabled)` | Enable/disable superscript | |
| `withSubscript(bool $enabled)` | Enable/disable subscript | |

### Unknown Bit Preservation

Like TextFormat, unknown bits are preserved when modifying known bits.

---

## Example

```php
<?php

declare(strict_types=1);

use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Bitmask\TextFormat;
use OpenSocial\RichTextJson\Bitmask\TextDetail;

// Create bold + italic text
$format = TextFormat::none()
    ->withBold(true)
    ->withItalic(true);

$text = new TextNode(1, 'Styled text', $format->getValue());

// Check formatting
$textFormat = TextFormat::fromNullable($text->getFormat());
if ($textFormat?->isBold()) {
    echo "Text is bold\n";
}

// Add superscript
$detail = TextDetail::none()->withSuperscript(true);
$superText = $text->withDetail($detail->getValue());
```

## See Also

- [Nodes](nodes.md) — TextNode uses these bitmasks
- [How-To: Edit Nodes](../how-to/edit-nodes.md) — Editing text formatting
