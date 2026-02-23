<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Bitmask;

/**
 * Value object for text detail bitmask.
 *
 * Known bits: 1=superscript, 2=subscript.
 * Unknown bits are preserved when modifying known bits.
 *
 * @see https://opensocial.github.io/rich-text-json-exchange/
 */
final class TextDetail {

  /**
   * Bit value for superscript.
   */
  private const SUPERSCRIPT = 1;

  /**
   * Bit value for subscript.
   */
  private const SUBSCRIPT = 2;

  /**
   * Creates a new TextDetail.
   *
   * @param int $value
   *   The bitmask value.
   */
  public function __construct(
    private readonly int $value,
  ) {}

  /**
   * Creates a TextDetail from a nullable integer.
   *
   * @param int|null $value
   *   The bitmask value, or NULL.
   *
   * @return self|null
   *   The TextDetail instance, or NULL if value is NULL.
   */
  public static function fromNullable(?int $value): ?self {
    if ($value === NULL) {
      return NULL;
    }
    return new self($value);
  }

  /**
   * Creates a TextDetail with no detail formatting.
   *
   * @return self
   *   A TextDetail with value 0.
   */
  public static function none(): self {
    return new self(0);
  }

  /**
   * Gets the raw bitmask value.
   *
   * @return int
   *   The bitmask value.
   */
  public function getValue(): int {
    return $this->value;
  }

  /**
   * Checks if superscript is enabled.
   *
   * @return bool
   *   TRUE if superscript bit is set.
   */
  public function isSuperscript(): bool {
    return ($this->value & self::SUPERSCRIPT) !== 0;
  }

  /**
   * Checks if subscript is enabled.
   *
   * @return bool
   *   TRUE if subscript bit is set.
   */
  public function isSubscript(): bool {
    return ($this->value & self::SUBSCRIPT) !== 0;
  }

  /**
   * Returns a new instance with superscript set or unset.
   *
   * @param bool $enabled
   *   Whether to enable or disable superscript.
   *
   * @return self
   *   A new TextDetail instance.
   */
  public function withSuperscript(bool $enabled): self {
    return $this->withBit(self::SUPERSCRIPT, $enabled);
  }

  /**
   * Returns a new instance with subscript set or unset.
   *
   * @param bool $enabled
   *   Whether to enable or disable subscript.
   *
   * @return self
   *   A new TextDetail instance.
   */
  public function withSubscript(bool $enabled): self {
    return $this->withBit(self::SUBSCRIPT, $enabled);
  }

  /**
   * Returns a new instance with a specific bit set or unset.
   *
   * @param int $bit
   *   The bit to modify.
   * @param bool $enabled
   *   Whether to enable or disable the bit.
   *
   * @return self
   *   A new TextDetail instance.
   */
  private function withBit(int $bit, bool $enabled): self {
    if ($enabled) {
      return new self($this->value | $bit);
    }
    return new self($this->value & ~$bit);
  }

}
