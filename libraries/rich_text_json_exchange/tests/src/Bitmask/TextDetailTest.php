<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Bitmask;

use OpenSocial\RichTextJson\Bitmask\TextDetail;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TextDetail value object.
 */
#[CoversClass(TextDetail::class)]
final class TextDetailTest extends TestCase {

  /**
   * Tests creating TextDetail from integer value.
   */
  public function testCreateFromValue(): void {
    $detail = new TextDetail(1);
    self::assertSame(1, $detail->getValue());
  }

  /**
   * Tests that zero value means no detail formatting.
   */
  public function testZeroValueMeansNoDetail(): void {
    $detail = new TextDetail(0);
    self::assertFalse($detail->isSuperscript());
    self::assertFalse($detail->isSubscript());
  }

  /**
   * Tests checking superscript bit (1).
   */
  public function testIsSuperscript(): void {
    $detail = new TextDetail(1);
    self::assertTrue($detail->isSuperscript());
    self::assertFalse($detail->isSubscript());
  }

  /**
   * Tests checking subscript bit (2).
   */
  public function testIsSubscript(): void {
    $detail = new TextDetail(2);
    self::assertFalse($detail->isSuperscript());
    self::assertTrue($detail->isSubscript());
  }

  /**
   * Tests that both bits can technically be set (even if semantically odd).
   */
  public function testBothBitsCanBeSet(): void {
    $detail = new TextDetail(3);
    self::assertTrue($detail->isSuperscript());
    self::assertTrue($detail->isSubscript());
  }

  /**
   * Tests enabling superscript returns new instance.
   */
  public function testWithSuperscriptReturnsNewInstance(): void {
    $detail = new TextDetail(0);
    $newDetail = $detail->withSuperscript(TRUE);

    self::assertNotSame($detail, $newDetail);
    self::assertFalse($detail->isSuperscript());
    self::assertTrue($newDetail->isSuperscript());
    self::assertSame(1, $newDetail->getValue());
  }

  /**
   * Tests disabling superscript.
   */
  public function testWithSuperscriptDisable(): void {
    $detail = new TextDetail(3);
    $newDetail = $detail->withSuperscript(FALSE);

    self::assertTrue($detail->isSuperscript());
    self::assertFalse($newDetail->isSuperscript());
    self::assertTrue($newDetail->isSubscript());
    self::assertSame(2, $newDetail->getValue());
  }

  /**
   * Tests enabling subscript.
   */
  public function testWithSubscript(): void {
    $detail = new TextDetail(0);
    $newDetail = $detail->withSubscript(TRUE);

    self::assertSame(2, $newDetail->getValue());
    self::assertTrue($newDetail->isSubscript());
  }

  /**
   * Tests disabling subscript.
   */
  public function testWithSubscriptDisable(): void {
    $detail = new TextDetail(2);
    $newDetail = $detail->withSubscript(FALSE);

    self::assertFalse($newDetail->isSubscript());
    self::assertSame(0, $newDetail->getValue());
  }

  /**
   * Tests that unknown bits are preserved when modifying known bits.
   */
  public function testUnknownBitsPreservedWhenModifyingKnownBits(): void {
    // Bit 4 is an unknown future bit.
    $detail = new TextDetail(4);

    // Enable superscript (bit 1).
    $newDetail = $detail->withSuperscript(TRUE);

    // Unknown bit 4 should still be set, plus superscript (1).
    self::assertSame(5, $newDetail->getValue());
    self::assertTrue($newDetail->isSuperscript());
  }

  /**
   * Tests unknown bits preserved through multiple modifications.
   */
  public function testUnknownBitsPreservedThroughMultipleModifications(): void {
    // Bits 8 and 16 are unknown future bits.
    $detail = new TextDetail(24);

    $modified = $detail
      ->withSuperscript(TRUE)
      ->withSubscript(TRUE)
      ->withSuperscript(FALSE);

    // Should have: subscript (2) + unknown bits (24) = 26.
    self::assertSame(26, $modified->getValue());
    self::assertFalse($modified->isSuperscript());
    self::assertTrue($modified->isSubscript());
  }

  /**
   * Tests creating TextDetail with null returns null.
   */
  public function testFromNullableWithNull(): void {
    $detail = TextDetail::fromNullable(NULL);
    self::assertNull($detail);
  }

  /**
   * Tests creating TextDetail from nullable integer.
   */
  public function testFromNullableWithValue(): void {
    $detail = TextDetail::fromNullable(1);
    self::assertNotNull($detail);
    self::assertSame(1, $detail->getValue());
  }

  /**
   * Tests default factory method creates no detail.
   */
  public function testNoneFactoryMethod(): void {
    $detail = TextDetail::none();
    self::assertSame(0, $detail->getValue());
    self::assertFalse($detail->isSuperscript());
    self::assertFalse($detail->isSubscript());
  }

  /**
   * Tests setting the same value doesn't change anything.
   */
  public function testSettingSameValueNoChange(): void {
    $detail = new TextDetail(1);
    $newDetail = $detail->withSuperscript(TRUE);

    self::assertSame(1, $newDetail->getValue());
  }

  /**
   * Data provider for known bits.
   *
   * @return array<string, array{int, string}>
   *   Test data.
   */
  public static function knownBitsProvider(): array {
    return [
      'superscript' => [1, 'isSuperscript'],
      'subscript' => [2, 'isSubscript'],
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
    $detail = new TextDetail($bit);
    self::assertTrue($detail->$method());
  }

}
