<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Bitmask;

use OpenSocial\RichTextJson\Bitmask\TextFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TextFormat value object.
 */
#[CoversClass(TextFormat::class)]
final class TextFormatTest extends TestCase {

  /**
   * Tests creating TextFormat from integer value.
   */
  public function testCreateFromValue(): void {
    $format = new TextFormat(3);
    self::assertSame(3, $format->getValue());
  }

  /**
   * Tests that zero value means no formatting.
   */
  public function testZeroValueMeansNoFormatting(): void {
    $format = new TextFormat(0);
    self::assertFalse($format->isBold());
    self::assertFalse($format->isItalic());
    self::assertFalse($format->isUnderline());
    self::assertFalse($format->isStrikethrough());
  }

  /**
   * Tests checking bold bit (1).
   */
  public function testIsBold(): void {
    $format = new TextFormat(1);
    self::assertTrue($format->isBold());
    self::assertFalse($format->isItalic());
    self::assertFalse($format->isUnderline());
    self::assertFalse($format->isStrikethrough());
  }

  /**
   * Tests checking italic bit (2).
   */
  public function testIsItalic(): void {
    $format = new TextFormat(2);
    self::assertFalse($format->isBold());
    self::assertTrue($format->isItalic());
    self::assertFalse($format->isUnderline());
    self::assertFalse($format->isStrikethrough());
  }

  /**
   * Tests checking underline bit (4).
   */
  public function testIsUnderline(): void {
    $format = new TextFormat(4);
    self::assertFalse($format->isBold());
    self::assertFalse($format->isItalic());
    self::assertTrue($format->isUnderline());
    self::assertFalse($format->isStrikethrough());
  }

  /**
   * Tests checking strikethrough bit (8).
   */
  public function testIsStrikethrough(): void {
    $format = new TextFormat(8);
    self::assertFalse($format->isBold());
    self::assertFalse($format->isItalic());
    self::assertFalse($format->isUnderline());
    self::assertTrue($format->isStrikethrough());
  }

  /**
   * Tests multiple bits set (bold + italic = 3).
   */
  public function testMultipleBitsSet(): void {
    $format = new TextFormat(3);
    self::assertTrue($format->isBold());
    self::assertTrue($format->isItalic());
    self::assertFalse($format->isUnderline());
    self::assertFalse($format->isStrikethrough());
  }

  /**
   * Tests all known bits set (1+2+4+8 = 15).
   */
  public function testAllKnownBitsSet(): void {
    $format = new TextFormat(15);
    self::assertTrue($format->isBold());
    self::assertTrue($format->isItalic());
    self::assertTrue($format->isUnderline());
    self::assertTrue($format->isStrikethrough());
  }

  /**
   * Tests enabling bold returns new instance.
   */
  public function testWithBoldReturnsNewInstance(): void {
    $format = new TextFormat(0);
    $newFormat = $format->withBold(TRUE);

    self::assertNotSame($format, $newFormat);
    self::assertFalse($format->isBold());
    self::assertTrue($newFormat->isBold());
    self::assertSame(1, $newFormat->getValue());
  }

  /**
   * Tests disabling bold.
   */
  public function testWithBoldDisable(): void {
    $format = new TextFormat(3);
    $newFormat = $format->withBold(FALSE);

    self::assertTrue($format->isBold());
    self::assertFalse($newFormat->isBold());
    self::assertTrue($newFormat->isItalic());
    self::assertSame(2, $newFormat->getValue());
  }

  /**
   * Tests enabling italic.
   */
  public function testWithItalic(): void {
    $format = new TextFormat(1);
    $newFormat = $format->withItalic(TRUE);

    self::assertSame(3, $newFormat->getValue());
    self::assertTrue($newFormat->isBold());
    self::assertTrue($newFormat->isItalic());
  }

  /**
   * Tests enabling underline.
   */
  public function testWithUnderline(): void {
    $format = new TextFormat(0);
    $newFormat = $format->withUnderline(TRUE);

    self::assertSame(4, $newFormat->getValue());
    self::assertTrue($newFormat->isUnderline());
  }

  /**
   * Tests enabling strikethrough.
   */
  public function testWithStrikethrough(): void {
    $format = new TextFormat(0);
    $newFormat = $format->withStrikethrough(TRUE);

    self::assertSame(8, $newFormat->getValue());
    self::assertTrue($newFormat->isStrikethrough());
  }

  /**
   * Tests that unknown bits are preserved when modifying known bits.
   */
  public function testUnknownBitsPreservedWhenModifyingKnownBits(): void {
    // Bit 16 is an unknown future bit.
    $format = new TextFormat(16);

    // Enable bold (bit 1).
    $newFormat = $format->withBold(TRUE);

    // Unknown bit 16 should still be set, plus bold (1).
    self::assertSame(17, $newFormat->getValue());
    self::assertTrue($newFormat->isBold());
  }

  /**
   * Tests unknown bits preserved through multiple modifications.
   */
  public function testUnknownBitsPreservedThroughMultipleModifications(): void {
    // Bits 32 and 64 are unknown future bits.
    $format = new TextFormat(96);

    $modified = $format
      ->withBold(TRUE)
      ->withItalic(TRUE)
      ->withBold(FALSE);

    // Should have: italic (2) + unknown bits (96) = 98.
    self::assertSame(98, $modified->getValue());
    self::assertFalse($modified->isBold());
    self::assertTrue($modified->isItalic());
  }

  /**
   * Tests creating TextFormat with null returns null.
   */
  public function testFromNullableWithNull(): void {
    $format = TextFormat::fromNullable(NULL);
    self::assertNull($format);
  }

  /**
   * Tests creating TextFormat from nullable integer.
   */
  public function testFromNullableWithValue(): void {
    $format = TextFormat::fromNullable(3);
    self::assertNotNull($format);
    self::assertSame(3, $format->getValue());
  }

  /**
   * Tests default factory method creates no formatting.
   */
  public function testNoneFactoryMethod(): void {
    $format = TextFormat::none();
    self::assertSame(0, $format->getValue());
    self::assertFalse($format->isBold());
    self::assertFalse($format->isItalic());
    self::assertFalse($format->isUnderline());
    self::assertFalse($format->isStrikethrough());
  }

  /**
   * Tests setting the same value doesn't change anything.
   */
  public function testSettingSameValueNoChange(): void {
    $format = new TextFormat(1);
    $newFormat = $format->withBold(TRUE);

    self::assertSame(1, $newFormat->getValue());
  }

  /**
   * Data provider for known bits.
   *
   * @return array<string, array{int, string}>
   *   Test data.
   */
  public static function knownBitsProvider(): array {
    return [
      'bold' => [1, 'isBold'],
      'italic' => [2, 'isItalic'],
      'underline' => [4, 'isUnderline'],
      'strikethrough' => [8, 'isStrikethrough'],
    ];
  }

  /**
   * Tests each known bit individually.
   *
   * @param int $bit
   *   The bit value.
   * @param string $method
   *   The check method name.
   */
  #[DataProvider('knownBitsProvider')]
  public function testEachKnownBit(int $bit, string $method): void {
    $format = new TextFormat($bit);
    self::assertTrue($format->$method());
  }

}
