<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ParagraphNode class.
 */
#[CoversClass(ParagraphNode::class)]
class ParagraphNodeTest extends TestCase {

  /**
   * Tests that a paragraph node parses correctly.
   */
  #[Test]
  public function paragraphNodeParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [],
          ],
        ],
      ],
    ]);

    $children = $document->getRoot()->getChildren();
    $this->assertCount(1, $children);
    $this->assertInstanceOf(ParagraphNode::class, $children[0]);
    $this->assertSame('paragraph', $children[0]->getType());
    $this->assertSame(1, $children[0]->getVersion());
  }

  /**
   * Tests that a paragraph with text children parses correctly.
   */
  #[Test]
  public function paragraphWithTextChildrenParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello'],
              ['type' => 'text', 'version' => 1, 'text' => ' World'],
            ],
          ],
        ],
      ],
    ]);

    $paragraph = $document->getRoot()->getChildren()[0];
    $this->assertInstanceOf(ParagraphNode::class, $paragraph);

    $textChildren = $paragraph->getChildren();
    $this->assertCount(2, $textChildren);
    $this->assertInstanceOf(TextNode::class, $textChildren[0]);
    $this->assertInstanceOf(TextNode::class, $textChildren[1]);
  }

  /**
   * Tests that paragraph round-trips correctly.
   */
  #[Test]
  public function paragraphRoundTrips(): void {
    $original = [
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
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that unknown fields on paragraph are preserved.
   */
  #[Test]
  public function unknownFieldsOnParagraphArePreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [],
            'customAttr' => 'preserved',
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that paragraph with higher version is preserved.
   */
  #[Test]
  public function paragraphWithHigherVersionIsPreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 99,
            'futureField' => 'value',
            'children' => [],
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    self::assertEquals($original, $result);
  }

}
