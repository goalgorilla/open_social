<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Editing;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Visitor\NodeTraverser;
use OpenSocial\RichTextJson\Visitor\NodeVisitorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for NodeTraverser.
 */
#[CoversClass(NodeTraverser::class)]
final class NodeTraverserTest extends TestCase {

  /**
   * Tests that traverser visits all nodes in document.
   */
  public function testTraverserVisitsAllNodes(): void {
    $json = json_encode([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello'],
              ['type' => 'text', 'version' => 1, 'text' => 'World'],
            ],
          ],
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Another'],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);

    $visitedTypes = [];
    $visitor = $this->createTypeCollectorVisitor($visitedTypes);

    $traverser = new NodeTraverser([$visitor]);
    $traverser->traverse($doc->getRoot());

    self::assertSame(['root', 'paragraph', 'text', 'text', 'paragraph', 'text'], $visitedTypes);
  }

  /**
   * Tests that visitor can transform text nodes.
   */
  public function testVisitorCanTransformTextNodes(): void {
    $json = json_encode([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'hello'],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);

    $visitor = $this->createUppercaseVisitor();

    $traverser = new NodeTraverser([$visitor]);
    $newRoot = $traverser->traverse($doc->getRoot());
    self::assertInstanceOf(RootNode::class, $newRoot);

    $array = $newRoot->toArray();
    self::assertArrayHasKey('children', $array);
    self::assertEquals([
      [
        'type' => 'paragraph',
        'version' => 1,
        'children' => [
          ['type' => 'text', 'version' => 1, 'text' => 'HELLO'],
        ],
      ],
    ], $array['children']);
  }

  /**
   * Tests that visitor can remove nodes by returning null.
   */
  public function testVisitorCanRemoveNodes(): void {
    $json = json_encode([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Keep'],
              ['type' => 'text', 'version' => 1, 'text' => 'Remove'],
              ['type' => 'text', 'version' => 1, 'text' => 'Also Keep'],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);

    $visitor = $this->createRemoveTextVisitor('Remove');

    $traverser = new NodeTraverser([$visitor]);
    $newRoot = $traverser->traverse($doc->getRoot());
    self::assertInstanceOf(RootNode::class, $newRoot);

    $array = $newRoot->toArray();
    self::assertArrayHasKey('children', $array);
    self::assertEquals([
      [
        'type' => 'paragraph',
        'version' => 1,
        'children' => [
          ['type' => 'text', 'version' => 1, 'text' => 'Keep'],
          ['type' => 'text', 'version' => 1, 'text' => 'Also Keep'],
        ],
      ],
    ], $array['children']);
  }

  /**
   * Tests that multiple visitors are applied in order.
   */
  public function testMultipleVisitorsAppliedInOrder(): void {
    $json = json_encode([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'hello'],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);

    $visitor1 = $this->createUppercaseVisitor();
    $visitor2 = $this->createAppendExclamationVisitor();

    $traverser = new NodeTraverser([$visitor1, $visitor2]);
    $newRoot = $traverser->traverse($doc->getRoot());
    self::assertInstanceOf(RootNode::class, $newRoot);

    $array = $newRoot->toArray();
    self::assertArrayHasKey('children', $array);
    self::assertEquals([
      [
        'type' => 'paragraph',
        'version' => 1,
        'children' => [
          ['type' => 'text', 'version' => 1, 'text' => 'HELLO!'],
        ],
      ],
    ], $array['children']);
  }

  /**
   * Tests traversing nested structures.
   */
  public function testTraverseNestedStructures(): void {
    $json = json_encode([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'quote',
            'version' => 1,
            'children' => [
              [
                'type' => 'paragraph',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'link',
                    'version' => 1,
                    'url' => 'https://example.com',
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => 'Click'],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);

    $visitedTypes = [];
    $visitor = $this->createTypeCollectorVisitor($visitedTypes);

    $traverser = new NodeTraverser([$visitor]);
    $traverser->traverse($doc->getRoot());

    self::assertSame(['root', 'quote', 'paragraph', 'link', 'text'], $visitedTypes);
  }

  /**
   * Tests that leaveNode is called after children are processed.
   */
  public function testLeaveNodeCalledAfterChildren(): void {
    $json = json_encode([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello'],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);

    $events = [];
    $visitor = $this->createEventCollectorVisitor($events);

    $traverser = new NodeTraverser([$visitor]);
    $traverser->traverse($doc->getRoot());

    self::assertSame([
      'enter:root',
      'enter:paragraph',
      'enter:text',
      'leave:text',
      'leave:paragraph',
      'leave:root',
    ], $events);
  }

  /**
   * Creates a visitor that collects node types.
   *
   * @param array<int, string> $visitedTypes
   *   Reference to array to collect types into.
   *
   * @return \OpenSocial\RichTextJson\Visitor\NodeVisitorInterface
   *   The visitor.
   */
  private function createTypeCollectorVisitor(array &$visitedTypes): NodeVisitorInterface {
    return new class ($visitedTypes) implements NodeVisitorInterface {

      /**
       * The visited types array.
       *
       * @var array<int, string>
       * @phpstan-ignore-next-line property.onlyWritten until https://github.com/phpstan/phpstan/issues/10068.
       */
      private array $visitedTypes;

      /**
       * Creates the visitor.
       *
       * @param array<int, string> $visitedTypes
       *   Reference to array to collect types into.
       */
      public function __construct(array &$visitedTypes) {
        $this->visitedTypes = &$visitedTypes;
      }

      /**
       * {@inheritdoc}
       */
      public function enterNode(NodeInterface $node): NodeInterface {
        $this->visitedTypes[] = $node->getType();
        return $node;
      }

      /**
       * {@inheritdoc}
       */
      public function leaveNode(NodeInterface $node): NodeInterface {
        return $node;
      }

    };
  }

  /**
   * Creates a visitor that uppercases text nodes.
   *
   * @return \OpenSocial\RichTextJson\Visitor\NodeVisitorInterface
   *   The visitor.
   */
  private function createUppercaseVisitor(): NodeVisitorInterface {
    return new class implements NodeVisitorInterface {

      /**
       * {@inheritdoc}
       */
      public function enterNode(NodeInterface $node): NodeInterface {
        if ($node instanceof TextNode) {
          return $node->withText(strtoupper($node->getText()));
        }
        return $node;
      }

      /**
       * {@inheritdoc}
       */
      public function leaveNode(NodeInterface $node): NodeInterface {
        return $node;
      }

    };
  }

  /**
   * Creates a visitor that removes text nodes with specific text.
   *
   * @param string $textToRemove
   *   The text to remove.
   *
   * @return \OpenSocial\RichTextJson\Visitor\NodeVisitorInterface
   *   The visitor.
   */
  private function createRemoveTextVisitor(string $textToRemove): NodeVisitorInterface {
    return new class ($textToRemove) implements NodeVisitorInterface {

      /**
       * The text to remove.
       *
       * @var string
       */
      private string $textToRemove;

      /**
       * Creates the visitor.
       *
       * @param string $textToRemove
       *   The text to remove.
       */
      public function __construct(string $textToRemove) {
        $this->textToRemove = $textToRemove;
      }

      /**
       * {@inheritdoc}
       */
      public function enterNode(NodeInterface $node): NodeInterface|null {
        if ($node instanceof TextNode && $node->getText() === $this->textToRemove) {
          return NULL;
        }
        return $node;
      }

      /**
       * {@inheritdoc}
       */
      public function leaveNode(NodeInterface $node): NodeInterface {
        return $node;
      }

    };
  }

  /**
   * Creates a visitor that appends exclamation to text nodes.
   *
   * @return \OpenSocial\RichTextJson\Visitor\NodeVisitorInterface
   *   The visitor.
   */
  private function createAppendExclamationVisitor(): NodeVisitorInterface {
    return new class implements NodeVisitorInterface {

      /**
       * {@inheritdoc}
       */
      public function enterNode(NodeInterface $node): NodeInterface {
        if ($node instanceof TextNode) {
          return $node->withText($node->getText() . '!');
        }
        return $node;
      }

      /**
       * {@inheritdoc}
       */
      public function leaveNode(NodeInterface $node): NodeInterface {
        return $node;
      }

    };
  }

  /**
   * Creates a visitor that collects enter/leave events.
   *
   * @param array<int, string> $events
   *   Reference to array to collect events into.
   *
   * @return \OpenSocial\RichTextJson\Visitor\NodeVisitorInterface
   *   The visitor.
   */
  private function createEventCollectorVisitor(array &$events): NodeVisitorInterface {
    return new class ($events) implements NodeVisitorInterface {

      /**
       * The events array.
       *
       * @var array<int, string>
       * @phpstan-ignore-next-line property.onlyWritten until https://github.com/phpstan/phpstan/issues/10068.
       */
      private array $events;

      /**
       * Creates the visitor.
       *
       * @param array<int, string> $events
       *   Reference to array to collect events into.
       */
      public function __construct(array &$events) {
        $this->events = &$events;
      }

      /**
       * {@inheritdoc}
       */
      public function enterNode(NodeInterface $node): NodeInterface {
        $this->events[] = 'enter:' . $node->getType();
        return $node;
      }

      /**
       * {@inheritdoc}
       */
      public function leaveNode(NodeInterface $node): NodeInterface {
        $this->events[] = 'leave:' . $node->getType();
        return $node;
      }

    };
  }

}
