<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Bitmask;

/**
 * Value object for text format bitmask.
 *
 * Known bits: 1=bold, 2=italic, 4=underline, 8=strikethrough.
 * Unknown bits are preserved when modifying known bits.
 *
 * @see https://opensocial.github.io/rich-text-json-exchange/
 */
final class TextFormat {

  /**
   * Bit value for bold.
   */
  private const BOLD = 1;

  /**
   * Bit value for italic.
   */
  private const ITALIC = 2;

  /**
   * Bit value for underline.
   */
  private const UNDERLINE = 4;

  /**
   * Bit value for strikethrough.
   */
  private const STRIKETHROUGH = 8;

  /**
   * Creates a new TextFormat.
   *
   * @param int $value
   *   The bitmask value.
   */
  public function __construct(
    private readonly int $value,
  ) {}

  /**
   * Creates a TextFormat from a nullable integer.
   *
   * @param int|null $value
   *   The bitmask value, or NULL.
   *
   * @return self|null
   *   The TextFormat instance, or NULL if value is NULL.
   */
  public static function fromNullable(?int $value): ?self {
    if ($value === NULL) {
      return NULL;
    }
    return new self($value);
  }

  /**
   * Creates a TextFormat with no formatting.
   *
   * @return self
   *   A TextFormat with value 0.
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
   * Checks if bold is enabled.
   *
   * @return bool
   *   TRUE if bold bit is set.
   */
  public function isBold(): bool {
    return ($this->value & self::BOLD) !== 0;
  }

  /**
   * Checks if italic is enabled.
   *
   * @return bool
   *   TRUE if italic bit is set.
   */
  public function isItalic(): bool {
    return ($this->value & self::ITALIC) !== 0;
  }

  /**
   * Checks if underline is enabled.
   *
   * @return bool
   *   TRUE if underline bit is set.
   */
  public function isUnderline(): bool {
    return ($this->value & self::UNDERLINE) !== 0;
  }

  /**
   * Checks if strikethrough is enabled.
   *
   * @return bool
   *   TRUE if strikethrough bit is set.
   */
  public function isStrikethrough(): bool {
    return ($this->value & self::STRIKETHROUGH) !== 0;
  }

  /**
   * Returns a new instance with bold set or unset.
   *
   * @param bool $enabled
   *   Whether to enable or disable bold.
   *
   * @return self
   *   A new TextFormat instance.
   */
  public function withBold(bool $enabled): self {
    return $this->withBit(self::BOLD, $enabled);
  }

  /**
   * Returns a new instance with italic set or unset.
   *
   * @param bool $enabled
   *   Whether to enable or disable italic.
   *
   * @return self
   *   A new TextFormat instance.
   */
  public function withItalic(bool $enabled): self {
    return $this->withBit(self::ITALIC, $enabled);
  }

  /**
   * Returns a new instance with underline set or unset.
   *
   * @param bool $enabled
   *   Whether to enable or disable underline.
   *
   * @return self
   *   A new TextFormat instance.
   */
  public function withUnderline(bool $enabled): self {
    return $this->withBit(self::UNDERLINE, $enabled);
  }

  /**
   * Returns a new instance with strikethrough set or unset.
   *
   * @param bool $enabled
   *   Whether to enable or disable strikethrough.
   *
   * @return self
   *   A new TextFormat instance.
   */
  public function withStrikethrough(bool $enabled): self {
    return $this->withBit(self::STRIKETHROUGH, $enabled);
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
   *   A new TextFormat instance.
   */
  private function withBit(int $bit, bool $enabled): self {
    if ($enabled) {
      return new self($this->value | $bit);
    }
    return new self($this->value & ~$bit);
  }

}
