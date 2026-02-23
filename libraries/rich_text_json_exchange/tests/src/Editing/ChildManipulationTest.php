<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Editing;

use OpenSocial\RichTextJson\Node\NodeFactory;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\TextNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for child manipulation methods on nodes.
 */
#[CoversClass(ParagraphNode::class)]
#[CoversClass(RootNode::class)]
final class ChildManipulationTest extends TestCase {

  /**
   * Tests that withChildren replaces all children.
   */
  public function testWithChildrenReplacesAllChildren(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Original'],
      ],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Replaced',
    ]);

    $newNode = $node->withChildren([$newChild]);

    self::assertCount(1, $node->getChildren());
    self::assertCount(1, $newNode->getChildren());

    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'Replaced'], $newNode->getChildren()[0]->toArray());
  }

  /**
   * Tests that appendChild adds child at end.
   */
  public function testAppendChildAddsAtEnd(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'First'],
      ],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Second',
    ]);

    $newNode = $node->appendChild($newChild);

    self::assertCount(1, $node->getChildren());
    self::assertCount(2, $newNode->getChildren());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'First'], $newNode->getChildren()[0]->toArray());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'Second'], $newNode->getChildren()[1]->toArray());
  }

  /**
   * Tests that insertChild at index 0 adds at beginning.
   */
  public function testInsertChildAtBeginning(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Second'],
      ],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'First',
    ]);

    $newNode = $node->insertChild(0, $newChild);

    self::assertCount(2, $newNode->getChildren());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'First'], $newNode->getChildren()[0]->toArray());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'Second'], $newNode->getChildren()[1]->toArray());
  }

  /**
   * Tests that insertChild at middle index works.
   */
  public function testInsertChildAtMiddle(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'First'],
        ['type' => 'text', 'version' => 1, 'text' => 'Third'],
      ],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Second',
    ]);

    $newNode = $node->insertChild(1, $newChild);

    self::assertCount(3, $newNode->getChildren());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'First'], $newNode->getChildren()[0]->toArray());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'Second'], $newNode->getChildren()[1]->toArray());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'Third'], $newNode->getChildren()[2]->toArray());
  }

  /**
   * Tests that insertChild at end index works (same as append).
   */
  public function testInsertChildAtEnd(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'First'],
      ],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Second',
    ]);

    $newNode = $node->insertChild(1, $newChild);

    self::assertCount(2, $newNode->getChildren());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'Second'], $newNode->getChildren()[1]->toArray());
  }

  /**
   * Tests that removeChild at valid index removes child.
   */
  public function testRemoveChildAtValidIndex(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'First'],
        ['type' => 'text', 'version' => 1, 'text' => 'Second'],
        ['type' => 'text', 'version' => 1, 'text' => 'Third'],
      ],
    ], $factory);

    $newNode = $node->removeChild(1);

    self::assertCount(3, $node->getChildren());
    self::assertCount(2, $newNode->getChildren());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'First'], $newNode->getChildren()[0]->toArray());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'Third'], $newNode->getChildren()[1]->toArray());
  }

  /**
   * Tests that replaceChild at valid index replaces child.
   */
  public function testReplaceChildAtValidIndex(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'First'],
        ['type' => 'text', 'version' => 1, 'text' => 'Old'],
        ['type' => 'text', 'version' => 1, 'text' => 'Third'],
      ],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'New',
    ]);

    $newNode = $node->replaceChild(1, $newChild);

    self::assertCount(3, $newNode->getChildren());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'First'], $newNode->getChildren()[0]->toArray());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'New'], $newNode->getChildren()[1]->toArray());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'Third'], $newNode->getChildren()[2]->toArray());
  }

  /**
   * Tests that insertChild with negative index throws exception.
   */
  public function testInsertChildNegativeIndexThrowsException(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Test',
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Index must be non-negative');
    $node->insertChild(-1, $newChild);
  }

  /**
   * Tests that insertChild with out of bounds index throws exception.
   */
  public function testInsertChildOutOfBoundsThrowsException(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Only'],
      ],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Test',
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Index out of bounds');
    $node->insertChild(5, $newChild);
  }

  /**
   * Tests that removeChild with negative index throws exception.
   */
  public function testRemoveChildNegativeIndexThrowsException(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Test'],
      ],
    ], $factory);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Index must be non-negative');
    $node->removeChild(-1);
  }

  /**
   * Tests that removeChild with out of bounds index throws exception.
   */
  public function testRemoveChildOutOfBoundsThrowsException(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Only'],
      ],
    ], $factory);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Index out of bounds');
    $node->removeChild(5);
  }

  /**
   * Tests that replaceChild with negative index throws exception.
   */
  public function testReplaceChildNegativeIndexThrowsException(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Test'],
      ],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'New',
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Index must be non-negative');
    $node->replaceChild(-1, $newChild);
  }

  /**
   * Tests that replaceChild with out of bounds index throws exception.
   */
  public function testReplaceChildOutOfBoundsThrowsException(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'Only'],
      ],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'New',
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Index out of bounds');
    $node->replaceChild(5, $newChild);
  }

  /**
   * Tests that unknown fields are preserved after child manipulation.
   */
  public function testUnknownFieldsPreservedAfterChildManipulation(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [],
      'customField' => 'preserved',
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'Test',
    ]);

    $newNode = $node->appendChild($newChild);
    $array = $newNode->toArray();

    // Ignore until https://github.com/phpstan/phpstan/issues/8438.
    /** @phpstan-ignore-next-line offsetAccess.notFound */
    self::assertSame('preserved', $array['customField']);
  }

  /**
   * Tests child manipulation on RootNode.
   */
  public function testRootNodeChildManipulation(): void {
    $factory = new NodeFactory();
    $root = RootNode::fromArray([
      'type' => 'root',
      'version' => 1,
      'children' => [
        ['type' => 'paragraph', 'version' => 1, 'children' => []],
      ],
    ], $factory);

    $newParagraph = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [
        ['type' => 'text', 'version' => 1, 'text' => 'New paragraph'],
      ],
    ], $factory);

    $newRoot = $root->appendChild($newParagraph);

    self::assertCount(1, $root->getChildren());
    self::assertCount(2, $newRoot->getChildren());
  }

  /**
   * Tests inserting into empty children array.
   */
  public function testInsertIntoEmptyChildren(): void {
    $factory = new NodeFactory();
    $node = ParagraphNode::fromArray([
      'type' => 'paragraph',
      'version' => 1,
      'children' => [],
    ], $factory);

    $newChild = TextNode::fromArray([
      'type' => 'text',
      'version' => 1,
      'text' => 'First',
    ]);

    $newNode = $node->insertChild(0, $newChild);

    self::assertCount(1, $newNode->getChildren());
    self::assertEquals(['type' => 'text', 'version' => 1, 'text' => 'First'], $newNode->getChildren()[0]->toArray());
  }

}
