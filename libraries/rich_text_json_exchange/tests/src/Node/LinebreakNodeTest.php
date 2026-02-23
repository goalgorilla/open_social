<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\LinebreakNode;
use OpenSocial\RichTextJson\Node\TextNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the LinebreakNode class.
 */
#[CoversClass(LinebreakNode::class)]
class LinebreakNodeTest extends TestCase {

  /**
   * Tests that a linebreak node parses correctly.
   */
  #[Test]
  public function linebreakNodeParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'linebreak', 'version' => 1],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    $children = $paragraph->getChildren();

    $this->assertCount(1, $children);
    $this->assertInstanceOf(LinebreakNode::class, $children[0]);
    $this->assertSame('linebreak', $children[0]->getType());
    $this->assertSame(1, $children[0]->getVersion());
  }

  /**
   * Tests that linebreak between text nodes parses correctly.
   */
  #[Test]
  public function linebreakBetweenTextNodesParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Line 1'],
              ['type' => 'linebreak', 'version' => 1],
              ['type' => 'text', 'version' => 1, 'text' => 'Line 2'],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    $children = $paragraph->getChildren();

    $this->assertCount(3, $children);
    $this->assertInstanceOf(TextNode::class, $children[0]);
    $this->assertInstanceOf(LinebreakNode::class, $children[1]);
    $this->assertInstanceOf(TextNode::class, $children[2]);
  }

  /**
   * Tests that linebreak node round-trips correctly.
   */
  #[Test]
  public function linebreakNodeRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Before'],
              ['type' => 'linebreak', 'version' => 1],
              ['type' => 'text', 'version' => 1, 'text' => 'After'],
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
   * Tests that unknown fields on linebreak node are preserved.
   */
  #[Test]
  public function unknownFieldsOnLinebreakNodeArePreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'linebreak',
                'version' => 1,
                'customAttr' => 'preserved',
              ],
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
   * Tests that linebreak with higher version is preserved.
   */
  #[Test]
  public function linebreakWithHigherVersionIsPreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'linebreak',
                'version' => 5,
                'futureField' => 'value',
              ],
            ],
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    self::assertEquals($original, $result);
  }

}
