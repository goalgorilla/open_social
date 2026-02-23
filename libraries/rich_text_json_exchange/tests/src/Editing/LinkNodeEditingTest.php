<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Editing;

use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Node\NodeFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for LinkNode editing methods.
 */
#[CoversClass(LinkNode::class)]
final class LinkNodeEditingTest extends TestCase {

  /**
   * Tests that withUrl returns a new instance.
   */
  public function testWithUrlReturnsNewInstance(): void {
    $factory = new NodeFactory();
    $node = LinkNode::fromArray([
      'type' => 'link',
      'version' => 1,
      'url' => 'https://example.com',
    ], $factory);

    $newNode = $node->withUrl('https://other.com');

    self::assertNotSame($node, $newNode);
    self::assertSame('https://example.com', $node->getUrl());
    self::assertSame('https://other.com', $newNode->getUrl());
  }

  /**
   * Tests that editing URL is reflected in serialization.
   */
  public function testEditingUrlReflectedInSerialization(): void {
    $factory = new NodeFactory();
    $node = LinkNode::fromArray([
      'type' => 'link',
      'version' => 1,
      'url' => 'https://original.com',
    ], $factory);

    $newNode = $node->withUrl('https://modified.com');
    $array = $newNode->toArray();

    self::assertSame('https://modified.com', $array['url']);
  }

  /**
   * Tests that withTitle returns a new instance.
   */
  public function testWithTitleReturnsNewInstance(): void {
    $factory = new NodeFactory();
    $node = LinkNode::fromArray([
      'type' => 'link',
      'version' => 1,
      'url' => 'https://example.com',
      'title' => 'Original',
    ], $factory);

    $newNode = $node->withTitle('Modified');

    self::assertNotSame($node, $newNode);
    self::assertSame('Original', $node->getTitle());
    self::assertSame('Modified', $newNode->getTitle());
  }

  /**
   * Tests setting title to null.
   */
  public function testWithTitleNull(): void {
    $factory = new NodeFactory();
    $node = LinkNode::fromArray([
      'type' => 'link',
      'version' => 1,
      'url' => 'https://example.com',
      'title' => 'Has Title',
    ], $factory);

    $newNode = $node->withTitle(NULL);
    $array = $newNode->toArray();

    self::assertNull($newNode->getTitle());
    self::assertArrayNotHasKey('title', $array);
  }

  /**
   * Tests that unknown fields are preserved after editing.
   */
  public function testUnknownFieldsPreservedAfterEditing(): void {
    $factory = new NodeFactory();
    $node = LinkNode::fromArray([
      'type' => 'link',
      'version' => 1,
      'url' => 'https://example.com',
      'customField' => 'preserved',
    ], $factory);

    $newNode = $node->withUrl('https://other.com');
    $array = $newNode->toArray();

    self::assertEquals([
      'type' => 'link',
      'version' => 1,
      'url' => 'https://other.com',
      'customField' => 'preserved',
    ], $array);
  }

  /**
   * Tests that children are preserved after editing URL.
   */
  public function testChildrenPreservedAfterEditing(): void {
    $factory = new NodeFactory();
    $node = LinkNode::fromArray([
      'type' => 'link',
      'version' => 1,
      'url' => 'https://example.com',
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Click me'],
      ],
    ], $factory);

    $newNode = $node->withUrl('https://other.com');
    $array = $newNode->toArray();

    self::assertEquals([
      'type' => 'link',
      'version' => 1,
      'url' => 'https://other.com',
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Click me'],
      ],
    ], $array);
  }

  /**
   * Tests that version is preserved after editing.
   */
  public function testVersionPreservedAfterEditing(): void {
    $factory = new NodeFactory();
    $node = LinkNode::fromArray([
      'type' => 'link',
      'version' => 2,
      'url' => 'https://example.com',
    ], $factory);

    $newNode = $node->withUrl('https://other.com');

    self::assertSame(2, $newNode->getVersion());
  }

}
