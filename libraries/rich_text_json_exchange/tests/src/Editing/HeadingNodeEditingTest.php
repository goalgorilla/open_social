<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Editing;

use OpenSocial\RichTextJson\Node\HeadingNode;
use OpenSocial\RichTextJson\Node\NodeFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for HeadingNode editing methods.
 */
#[CoversClass(HeadingNode::class)]
final class HeadingNodeEditingTest extends TestCase {

  /**
   * Tests that withLevel returns a new instance.
   */
  public function testWithLevelReturnsNewInstance(): void {
    $factory = new NodeFactory();
    $node = HeadingNode::fromArray([
      'type' => 'heading',
      'version' => 1,
      'level' => 1,
    ], $factory);

    $newNode = $node->withLevel(2);

    self::assertNotSame($node, $newNode);
    self::assertSame(1, $node->getLevel());
    self::assertSame(2, $newNode->getLevel());
  }

  /**
   * Tests that editing level is reflected in serialization.
   */
  public function testEditingLevelReflectedInSerialization(): void {
    $factory = new NodeFactory();
    $node = HeadingNode::fromArray([
      'type' => 'heading',
      'version' => 1,
      'level' => 1,
    ], $factory);

    $newNode = $node->withLevel(3);
    $array = $newNode->toArray();

    self::assertSame(3, $array['level']);
  }

  /**
   * Tests that unknown fields are preserved after editing.
   */
  public function testUnknownFieldsPreservedAfterEditing(): void {
    $factory = new NodeFactory();
    $node = HeadingNode::fromArray([
      'type' => 'heading',
      'version' => 1,
      'level' => 1,
      'customField' => 'preserved',
    ], $factory);

    $newNode = $node->withLevel(2);
    $array = $newNode->toArray();

    self::assertEquals([
      'type' => 'heading',
      'version' => 1,
      'level' => 2,
      'customField' => 'preserved',
    ], $array);
  }

  /**
   * Tests that children are preserved after editing level.
   */
  public function testChildrenPreservedAfterEditing(): void {
    $factory = new NodeFactory();
    $node = HeadingNode::fromArray([
      'type' => 'heading',
      'version' => 1,
      'level' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Title'],
      ],
    ], $factory);

    $newNode = $node->withLevel(2);
    $array = $newNode->toArray();

    self::assertEquals([
      'type' => 'heading',
      'version' => 1,
      'level' => 2,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Title'],
      ],
    ], $array);
  }

  /**
   * Tests that version is preserved after editing.
   */
  public function testVersionPreservedAfterEditing(): void {
    $factory = new NodeFactory();
    $node = HeadingNode::fromArray([
      'type' => 'heading',
      'version' => 2,
      'level' => 1,
    ], $factory);

    $newNode = $node->withLevel(3);

    self::assertSame(2, $newNode->getVersion());
  }

}
