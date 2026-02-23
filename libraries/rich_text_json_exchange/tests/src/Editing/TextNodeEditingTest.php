<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Editing;

use OpenSocial\RichTextJson\Node\TextNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TextNode editing methods.
 */
#[CoversClass(TextNode::class)]
final class TextNodeEditingTest extends TestCase {

  /**
   * Tests that withText returns a new instance.
   */
  public function testWithTextReturnsNewInstance(): void {
    $node = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Hello',
    ]);

    $newNode = $node->withText('World');

    self::assertNotSame($node, $newNode);
    self::assertSame('Hello', $node->getText());
    self::assertSame('World', $newNode->getText());
  }

  /**
   * Tests that editing text is reflected in serialization.
   */
  public function testEditingTextReflectedInSerialization(): void {
    $node = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Original',
    ]);

    $newNode = $node->withText('Modified');
    $array = $newNode->toArray();

    self::assertSame('Modified', $array['text']);
  }

  /**
   * Tests that withFormat returns a new instance.
   */
  public function testWithFormatReturnsNewInstance(): void {
    $node = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Hello',
      'format' => 1,
    ]);

    $newNode = $node->withFormat(3);

    self::assertNotSame($node, $newNode);
    self::assertSame(1, $node->getFormat());
    self::assertSame(3, $newNode->getFormat());
  }

  /**
   * Tests that editing format preserves other fields.
   */
  public function testEditingFormatPreservesOtherFields(): void {
    $node = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Hello',
      'format' => 1,
      'detail' => 2,
    ]);

    $newNode = $node->withFormat(15);
    $array = $newNode->toArray();

    self::assertEquals([
      'type' => 'text',
      'version' => 1,
      'text' => 'Hello',
      'format' => 15,
      'detail' => 2,
    ], $array);
  }

  /**
   * Tests that withDetail returns a new instance.
   */
  public function testWithDetailReturnsNewInstance(): void {
    $node = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Hello',
      'detail' => 1,
    ]);

    $newNode = $node->withDetail(2);

    self::assertNotSame($node, $newNode);
    self::assertSame(1, $node->getDetail());
    self::assertSame(2, $newNode->getDetail());
  }

  /**
   * Tests that unknown fields are preserved after editing.
   */
  public function testUnknownFieldsPreservedAfterEditing(): void {
    $node = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Hello',
      'customField' => 'preserved',
      'anotherField' => 42,
    ]);

    $newNode = $node->withText('World');
    $array = $newNode->toArray();

    self::assertEquals([
      'type' => 'text',
      'version' => 1,
      'text' => 'World',
      'customField' => 'preserved',
      'anotherField' => 42,
    ], $array);
  }

  /**
   * Tests that version is preserved after editing.
   */
  public function testVersionPreservedAfterEditing(): void {
    $node = TextNode::fromArray([
      'type' => 'text',
      'version' => 2,
      'text' => 'Hello',
    ]);

    $newNode = $node->withText('World');

    self::assertSame(2, $newNode->getVersion());
  }

  /**
   * Tests setting format to null.
   */
  public function testWithFormatNull(): void {
    $node = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Hello',
      'format' => 3,
    ]);

    $newNode = $node->withFormat(NULL);
    $array = $newNode->toArray();

    self::assertNull($newNode->getFormat());
    self::assertArrayNotHasKey('format', $array);
  }

  /**
   * Tests setting detail to null.
   */
  public function testWithDetailNull(): void {
    $node = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Hello',
      'detail' => 1,
    ]);

    $newNode = $node->withDetail(NULL);
    $array = $newNode->toArray();

    self::assertNull($newNode->getDetail());
    self::assertArrayNotHasKey('detail', $array);
  }

}
